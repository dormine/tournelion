<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand('app:create-admin', 'Crée un compte administrateur (ou change son mot de passe)')]
final class CreateAdminCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function __invoke(SymfonyStyle $io, #[Argument('Adresse e-mail')] string $email): int
    {
        $password = $io->askHidden('Mot de passe');
        if (null === $password || \strlen($password) < 12) {
            $io->error('12 caractères minimum.');

            return Command::FAILURE;
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]) ?? (new User())->setEmail($email);
        $user->setRoles(['ROLE_ADMIN'])->setPassword($this->hasher->hashPassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        $io->success('Administrateur '.$email.' enregistré.');

        return Command::SUCCESS;
    }
}
