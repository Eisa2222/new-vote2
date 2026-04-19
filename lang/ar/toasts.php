<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Toast messages
|--------------------------------------------------------------------------
| Short success / error / warning strings used by the toaster component
| and various controllers. Kept out of ar.json so they're grep-able as a
| group when auditing UX wording.
*/

return [
    // Generic
    'success'            => 'تم',
    'warning'            => 'تنبيه',
    'error'              => 'خطأ',

    // Users
    'user_created'       => 'تم إنشاء المستخدم.',
    'user_updated'       => 'تم تحديث المستخدم.',
    'user_deleted'       => 'تم حذف المستخدم.',
    'user_activated'     => 'تم تفعيل المستخدم.',
    'user_deactivated'   => 'تم تعطيل المستخدم.',

    // Roles
    'role_created'       => 'تم إنشاء الدور.',
    'role_updated'       => 'تم تحديث الدور.',
    'role_deleted'       => 'تم حذف الدور.',

    // Campaigns
    'campaign_created'   => 'تم إنشاء الحملة.',
    'campaign_updated'   => 'تم تحديث الحملة.',
    'campaign_deleted'   => 'تم حذف الحملة.',
    'campaign_published' => 'تم نشر الحملة.',
    'campaign_activated' => 'تم تفعيل الحملة.',
    'campaign_closed'    => 'تم إغلاق الحملة.',
    'campaign_archived'  => 'تم أرشفة الحملة.',
    'campaign_submitted' => 'تم إرسال الحملة للجنة للاعتماد.',
    'campaign_approved'  => 'تم اعتماد الحملة. أصبحت منشورة.',
    'campaign_rejected'  => 'تم رفض الحملة. يمكن للمسؤول تعديلها وإعادة إرسالها.',

    // Results
    'results_calculated' => 'تم إعادة احتساب النتائج.',
    'results_approved'   => 'تم اعتماد النتائج.',
    'results_hidden'     => 'تم إخفاء النتائج.',
    'results_announced'  => 'تم إعلان النتائج.',
    'tie_resolved'       => 'تم حسم التعادل.',

    // Clubs
    'club_created'       => 'تم إنشاء النادي.',
    'club_updated'       => 'تم تحديث النادي.',
    'club_deleted'       => 'تم حذف النادي.',
    'club_activated'     => 'تم تفعيل النادي.',
    'club_deactivated'   => 'تم تعطيل النادي.',

    // Players
    'player_created'     => 'تم إنشاء اللاعب.',
    'player_updated'     => 'تم تحديث اللاعب.',
    'player_deleted'     => 'تم حذف اللاعب.',
];
