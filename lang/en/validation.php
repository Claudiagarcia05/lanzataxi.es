<?php

return [
    'accepted' => 'The :attribute field must be accepted.',
    'active_url' => 'The :attribute field must be a valid URL.',
    'after' => 'The :attribute field must be a date after :date.',
    'after_or_equal' => 'The :attribute field must be a date after or equal to :date.',
    'before' => 'The :attribute field must be a date before :date.',
    'before_or_equal' => 'The :attribute field must be a date before or equal to :date.',
    'between' => [
        'numeric' => 'The :attribute field must be between :min and :max.',
        'file' => 'The :attribute file must be between :min and :max kilobytes.',
        'string' => 'The :attribute field must be between :min and :max characters.',
        'array' => 'The :attribute field must have between :min and :max items.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'date' => 'The :attribute field must be a valid date.',
    'email' => 'The :attribute field must be a valid email address.',
    'exists' => 'The selected :attribute is invalid.',
    'in' => 'The selected :attribute is invalid.',
    'integer' => 'The :attribute field must be an integer.',
    'max' => [
        'numeric' => 'The :attribute field must not be greater than :max.',
        'file' => 'The :attribute file must not be greater than :max kilobytes.',
        'string' => 'The :attribute field must not be greater than :max characters.',
        'array' => 'The :attribute field must not have more than :max items.',
    ],
    'min' => [
        'numeric' => 'The :attribute field must be at least :min.',
        'file' => 'The :attribute file must be at least :min kilobytes.',
        'string' => 'The :attribute field must be at least :min characters.',
        'array' => 'The :attribute field must have at least :min items.',
    ],
    'numeric' => 'The :attribute field must be a number.',
    'required' => 'The :attribute field is required.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'string' => 'The :attribute field must be a string.',
    'unique' => 'The :attribute has already been taken.',

    'attributes' => [
        'email' => 'email',
        'password' => 'password',
        'name' => 'name',
        'rating' => 'rating',
        'comment' => 'comment',
        'locale' => 'language',
    ],
];
