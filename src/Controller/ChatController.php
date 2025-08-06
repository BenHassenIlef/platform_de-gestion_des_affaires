<?php
namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ChatController extends AbstractController
{
    #[Route('/chat', name: 'chat')]
    public function chat(UserRepository $userRepository, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');
        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles) && !in_array('ROLE_RH', $roles)) {
            throw $this->createAccessDeniedException();
        }
        $otherUser = null;
        $otherId = $request->query->get('other');
        if ($otherId) {
            $otherUser = $userRepository->find($otherId);
        }
        if (!$otherUser) {
            // Fallback: Find the other role user
            $otherRole = in_array('ROLE_ADMIN', $roles) ? 'ROLE_RH' : 'ROLE_ADMIN';
            $otherUser = $userRepository->createQueryBuilder('u')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%"' . $otherRole . '"%')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
        }
        return $this->render('chat.html.twig', [
            'otherUser' => $otherUser
        ]);
    }

    #[Route('/chat/messages', name: 'chat_messages', methods: ['GET'])]
    public function getMessages(Request $request, MessageRepository $messageRepository, UserRepository $userRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse([], 403);
        $otherId = $request->query->get('other');
        $otherUser = $userRepository->find($otherId);
        if (!$otherUser) return new JsonResponse([], 404);
        $messages = $messageRepository->createQueryBuilder('m')
            ->where('(m.sender = :me AND m.receiver = :other) OR (m.sender = :other AND m.receiver = :me)')
            ->setParameter('me', $user)
            ->setParameter('other', $otherUser)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()->getResult();
        $data = array_map(function(Message $msg) {
            return [
                'id' => $msg->getId(),
                'sender' => $msg->getSender()->getEmail(),
                'receiver' => $msg->getReceiver()->getEmail(),
                'content' => $msg->getContent(),
                'createdAt' => $msg->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $messages);
        return new JsonResponse($data);
    }

    #[Route('/chat/send', name: 'chat_send', methods: ['POST'])]
    public function sendMessage(Request $request, EntityManagerInterface $em, UserRepository $userRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['error' => 'Unauthorized'], 403);
        $receiverId = $request->request->get('receiver');
        $content = trim($request->request->get('content'));
        if (!$receiverId || !$content) return new JsonResponse(['error' => 'Missing data'], 400);
        $receiver = $userRepository->find($receiverId);
        if (!$receiver) return new JsonResponse(['error' => 'Receiver not found'], 404);
        $msg = new Message();
        $msg->setSender($user);
        $msg->setReceiver($receiver);
        $msg->setContent($content);
        $msg->setCreatedAt(new \DateTime('now', new \DateTimeZone('Africa/Tunis')));
        $em->persist($msg);
        $em->flush();
        return new JsonResponse(['success' => true]);
    }
} 