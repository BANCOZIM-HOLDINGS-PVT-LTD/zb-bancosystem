<?php

namespace App\Filament\Agent\Pages;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as AuthenticationResponse;
use Illuminate\Validation\ValidationException;
use App\Models\Agent;
use Illuminate\Support\Facades\Auth;

class AgentLogin extends BaseLogin
{
    protected static string $view = 'filament.agent.pages.agent-login';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getAgentCodeFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getAgentCodeFormComponent(): Component
    {
        return TextInput::make('agent_code')
            ->label('Agent Code')
            ->placeholder('Enter your agent code')
            ->required()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'agent_code' => $data['agent_code'],
        ];
    }

    public function authenticate(): ?AuthenticationResponse
    {
        try {
            $data = $this->form->getState();

            // Find agent by agent code
            $agent = Agent::where('agent_code', $data['agent_code'])->first();

            if (!$agent) {
                throw ValidationException::withMessages([
                    'data.agent_code' => __('Invalid agent code.'),
                ]);
            }

            // Check if agent is active
            if ($agent->status !== 'active') {
                throw ValidationException::withMessages([
                    'data.agent_code' => __('Your agent account is not active. Please contact support.'),
                ]);
            }

            // Log the agent in
            Auth::guard('agent')->login($agent);

            session()->regenerate();

            return app(AuthenticationResponse::class);
        } catch (ValidationException $exception) {
            throw $exception;
        }
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.agent_code' => __('Invalid agent code.'),
        ]);
    }
}
