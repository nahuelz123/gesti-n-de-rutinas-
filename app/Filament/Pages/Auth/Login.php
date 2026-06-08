<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirect('/admin', navigate: false);
            return;
        }
        
        parent::mount();
    }
}
