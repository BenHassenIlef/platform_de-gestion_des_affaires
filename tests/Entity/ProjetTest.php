<?php

namespace App\Tests\Entity;

use App\Entity\Projet;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProjetTest extends KernelTestCase
{
    public function testGetSetNom()
    {
        $projet = new Projet();
        $nom = 'Project Name';
        $projet->setNom($nom);
        $this->assertEquals($nom, $projet->getNom());
    }

    public function testGetSetSociete()
    {
        $projet = new Projet();
        $societe = 'Societe Name';
        $projet->setSociete($societe);
        $this->assertEquals($societe, $projet->getSociete());
    }

    public function testGetSetDatePub()
    {
        $projet = new Projet();
        $datePub = new \DateTime('2023-07-10');
        $projet->setDatePub($datePub);
        $this->assertEquals($datePub, $projet->getDatePub());
    }

    public function testGetSetDeadline()
    {
        $projet = new Projet();
        $deadline = new \DateTime('2023-12-31');
        $projet->setDeadline($deadline);
        $this->assertEquals($deadline, $projet->getDeadline());
    }

    public function testGetSetCahierDeCharge()
    {
        $projet = new Projet();
        $cahierDeCharge = 'Cahier de Charge';
        $projet->setCahierDeCharge($cahierDeCharge);
        $this->assertEquals($cahierDeCharge, $projet->getCahierDeCharge());
    }

    public function testGetSetSuivi()
    {
        $projet = new Projet();
        $suivi = 'Suivi';
        $projet->setSuivi($suivi);
        $this->assertEquals($suivi, $projet->getSuivi());
    }

    public function testGetSetAvis()
    {
        $projet = new Projet();
        $avis = 'Avis';
        $projet->setAvis($avis);
        $this->assertEquals($avis, $projet->getAvis());
    }

    public function testGetSetMotif()
    {
        $projet = new Projet();
        $motif = 'Motif';
        $projet->setMotif($motif);
        $this->assertEquals($motif, $projet->getMotif());
    }
}
