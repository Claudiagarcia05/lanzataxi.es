<?php

    namespace App\Http\Requests;

    use App\Models\User;
    use App\Rules\EmailDomainHasMx;
    use Illuminate\Foundation\Http\FormRequest;
    use Illuminate\Validation\Rule;

    class ProfileUpdateRequest extends FormRequest {
        /**
         * Get the validation rules that apply to the request.
         *
         * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
         */
        public function rules(): array {
            $esTesting = app()->environment('testing');

            return [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'lowercase',
                    $esTesting ? 'email:rfc' : 'email:rfc,dns',
                    $esTesting ? null : new EmailDomainHasMx(),
                    'max:255',
                    Rule::unique(User::class)->ignore($this->user()->id),
                ],
            ];
        }
    }