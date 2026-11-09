<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;

class ErrorController extends Controller
{
    public function initialize(): void
    {
        $this->viewBuilder()->setTemplatePath('Error')->setLayout('error');
    }
}
