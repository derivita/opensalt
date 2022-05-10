<?php

namespace App\Console\User;

use App\Command\User\RemoveUserRoleCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

#[AsCommand('salt:user:remove-role', 'Remove a role from a local user')]
class UserRemoveRoleCommand extends UserRoleCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Email address or username of the user to change')
            ->addArgument('role', InputArgument::REQUIRED, 'Role to remove from the user (editor, admin, super-user)')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        parent::interact($input, $output);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        if (empty($input->getArgument('role'))) {
            $question = new ChoiceQuestion('Role to remove from the user: ', ['viewer', 'editor', 'admin', 'super user'], 0);
            $role = $helper->ask($input, $output, $question);
            $input->setArgument('role', $role);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (0 !== $this->doChange($input, $output, RemoveUserRoleCommand::class)) {
            return 1;
        }

        $output->writeln(sprintf('The role "%s" has been removed.', $input->getArgument('role')));

        return 0;
    }
}
