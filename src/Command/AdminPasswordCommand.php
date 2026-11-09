<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use function Cake\Core\env;

class AdminPasswordCommand extends Command
{
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser->setDescription('Inicializa o cambia la contraseña de una cuenta ADMIN existente sin publicarla en los argumentos del proceso.')
            ->addArgument('email', ['required' => true, 'help' => 'Correo de la cuenta administradora.']);
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $users = $this->fetchTable('Users');
        $email = mb_strtolower(trim((string)$args->getArgument('email')));
        $user = $users->find()->contain(['Roles'])->where(['Users.email' => $email])->first();
        if (!$user || $user->role->code !== 'ADMIN') {
            $io->err('La cuenta no existe o no tiene el rol ADMIN. Importa primero el SQL inicial.');

            return self::CODE_ERROR;
        }
        $password = env('ADMIN_PASSWORD');
        if (!is_string($password) || $password === '') {
            $io->err('Define temporalmente ADMIN_PASSWORD en el entorno. El README explica cómo capturarla sin mostrarla en pantalla.');

            return self::CODE_ERROR;
        }
        $user = $users->patchEntity($user, ['password' => $password]);
        if (!$users->save($user)) {
            foreach ($user->getError('password') as $error) {
                $io->err((string)$error);
            }

            return self::CODE_ERROR;
        }
        $io->success('Contraseña administradora actualizada. Ya puedes iniciar sesión.');

        return self::CODE_SUCCESS;
    }
}
