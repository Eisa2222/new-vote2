<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Human-readable permission descriptions
|--------------------------------------------------------------------------
| Nested by module so Laravel's dot-notation lookup resolves cleanly
| (e.g. __('permissions.clubs.viewAny')). The role-edit view calls
| __('permissions.'.$permission->name).
*/

return [
    'clubs' => [
        'viewAny' => 'عرض قائمة الأندية',
        'create'  => 'إنشاء نادٍ جديد',
        'update'  => 'تعديل بيانات الأندية',
        'delete'  => 'حذف الأندية',
    ],
    'players' => [
        'viewAny' => 'عرض قائمة اللاعبين',
        'create'  => 'إضافة لاعبين جدد',
        'update'  => 'تعديل بيانات اللاعبين',
        'delete'  => 'حذف اللاعبين',
    ],
    'campaigns' => [
        'viewAny' => 'عرض قائمة الحملات',
        'create'  => 'إنشاء حملات جديدة',
        'update'  => 'تعديل بيانات الحملات',
        'publish' => 'تفعيل/نشر الحملات',
        'close'   => 'إغلاق الحملات',
        'archive' => 'أرشفة الحملات',
        'delete'  => 'حذف الحملات',
        'approve' => 'اعتماد الحملات (لجنة)',
    ],
    'results' => [
        'view'      => 'عرض النتائج',
        'calculate' => 'احتساب النتائج',
        'approve'   => 'اعتماد النتائج (لجنة)',
        'hide'      => 'إخفاء النتائج بعد الإعلان',
        'announce'  => 'إعلان النتائج للعامة',
    ],
    'users' => [
        'manage' => 'إدارة المستخدمين والأدوار',
    ],
];
