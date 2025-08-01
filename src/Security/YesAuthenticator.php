<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class YesAuthenticator extends AbstractLoginFormAuthenticator
{
    public const LOGIN_ROUTE = 'app_login';
    private $urlGenerator;
    private UserRepository $userRepository;
    private RouterInterface $router; //to redirect when login

    public function __construct(UserRepository $userRepository, RouterInterface $router, UrlGeneratorInterface $urlGenerator)
    {
        $this->userRepository = $userRepository;
        $this->router = $router;
        $this->urlGenerator = $urlGenerator;
    }

    public function support(Request $request): ?bool
    {
        return $request->getPathInfo() === '/login' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $isRegister = $request->request->get('isRegister');

        // Gestion de l'inscription
        if ($isRegister) {
            // Vérifiez si l'utilisateur existe déjà
            $existingUser = $this->userRepository->findOneBy(['email' => $email]);
            if ($existingUser) {
                throw new AuthenticationException('Un utilisateur avec cet email existe déjà.');
            }
    
            // Créez un nouvel utilisateur
            $user = new User();
            $user->setEmail($email);
            $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
            $this->userRepository->save($user, true); // Assurez-vous que la méthode `save` existe dans `UserRepository`
    
            // Retournez un passeport pour le nouvel utilisateur
            return new Passport(
                new UserBadge($email, fn($userIdentifier) => $user),
                new CustomCredentials(fn($credentials, User $user) => true, $password)
            );
        }
    
        // Gestion de la connexion
        return new Passport(
            new UserBadge($email, function ($userIdentifier) {
                $user = $this->userRepository->findOneBy(['email' => $userIdentifier]);
                if (!$user) {
                    throw new UserNotFoundException();
                }
                return $user;
            }),
            new PasswordCredentials($password) // Vérifie le mot de passe avec bcrypt ou autre hash configuré
        );
    }       

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof \App\Entity\User && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse($this->router->generate('admin_home'));
        }
        // Les utilisateurs RH sont redirigés vers la page d'accueil RH
        return new RedirectResponse($this->router->generate('rh_home'));
    }
    
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // Ajouter un message d'erreur à la session (Flash Message)
        $request->getSession()->getFlashBag()->add('error', 'Veuillez vérifier votre identifiant ou mot de passe.');

        // Rediriger vers la page de connexion
        return new RedirectResponse($request->getUriForPath('/login'));
    }
}
