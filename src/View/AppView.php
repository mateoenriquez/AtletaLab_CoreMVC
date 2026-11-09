<?php
declare(strict_types=1);

namespace App\View;

use Cake\View\View;

class AppView extends View
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadHelper('Admin');
        $this->loadHelper('Form', ['templates' => [
            'inputContainer' => '<div class="input {{type}}{{required}}">{{content}}</div>',
            'inputContainerError' => '<div class="input {{type}}{{required}} has-error">{{content}}{{error}}</div>',
            'error' => '<div class="error-message" role="alert">{{content}}</div>',
        ]]);
    }
}
