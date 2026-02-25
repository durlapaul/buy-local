<?php

return [
    'unauthorized' => 'You are not authorized to perform this action.',
    'auth' => [
        'failed' => 'These credentials do not match our records.',
        'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
        'login_success' => 'Login successful',
        'logout_success' => 'Logged out successfully',
        'registration_success' => 'Registration successful',
    ],
    'validation' => [
        'required' => 'The :attribute field is required.',
        'email' => 'The :attribute must be a valid email address.',
        'unique' => 'The :attribute has already been taken.',
        'min' => [
            'string' => 'The :attribute must be at least :min characters.',
        ],
        'confirmed' => 'The :attribute confirmation does not match.',
    ],
    'user' => [
        'updated' => 'Profile updated successfully',
        'password_updated' => 'Password updated successfully',
        'password_incorrect' => 'Current password is incorrect',
        'account_deleted' => 'Account deleted successfully',
    ],
    'product' => [
        'created' => 'Product created successfully',
        'updated' => 'Product updated successfully'
    ]
];