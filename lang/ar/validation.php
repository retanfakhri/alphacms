<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'current_password' => 'كلمة المرور الحالية غير صحيحة.',
    'min' => [
        'string' => 'يجب أن يكون :attribute على الأقل :min حروف.',
    ],
    'attributes' => [
        'current_password' => 'كلمة المرور الحالية',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'username' => 'اسم المستخدم',
    ],
    'custom' => [
        'current_password' => [
            'required' => 'حقل كلمة المرور الحالية مطلوب.',
        ],
        'password' => [
            'required' => 'حقل كلمة المرور الجديدة مطلوب.',
        ],
    ],
];
