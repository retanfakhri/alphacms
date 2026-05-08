<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    protected array $modules = [
        'users' => ['name' => 'المستخدمون', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force_delete']],
        'writers' => ['name' => 'الكتّاب', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force_delete']],
        'admins' => ['name' => 'المشرفون', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force_delete']],

        'roles' => ['name' => 'الأدوار', 'actions' => ['view', 'create', 'update', 'delete']],
        'permissions' => ['name' => 'الصلاحيات', 'actions' => ['view', 'create', 'update', 'delete']],

        'articles' => ['name' => 'المقالات', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete', 'publish', 'unpublish']],
        'news' => ['name' => 'الأخبار', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete', 'publish', 'unpublish']],
        'news_submissions' => ['name' => 'أخبار الزوار (طلبات)', 'actions' => ['view', 'update', 'delete', 'publish']],
        'videos' => ['name' => 'الفيديوهات', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force_delete', 'publish', 'unpublish']],
        'reels' => ['name' => 'الريلز', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force_delete']],
        'pages' => ['name' => 'الصفحات الثابتة', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force_delete', 'publish', 'toggle']],

        'categories' => ['name' => 'الأقسام', 'actions' => ['view', 'create', 'update', 'delete']],
        'site_feeds' => ['name' => 'خرائط الموقع و RSS', 'actions' => ['view', 'invalidate']],
        'tags' => ['name' => 'الوسوم', 'actions' => ['view', 'create', 'update', 'delete']],

        'ads' => ['name' => 'الإعلانات', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force_delete']],

        'whatsapp_campaigns' => ['name' => 'حملات واتساب', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force_delete', 'send', 'sync']],
        'whatsapp_contacts' => ['name' => 'جهات اتصال واتساب', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force_delete', 'import', 'export']],
        'whatsapp_groups' => ['name' => 'مجموعات واتساب', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force_delete']],
        'whatsapp_cron' => ['name' => 'تشغيل كرون واتساب', 'actions' => ['view', 'run']],

        'polls' => ['name' => 'الاستطلاعات', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force_delete', 'toggle']],

        'comments' => ['name' => 'التعليقات', 'actions' => ['view', 'update', 'delete', 'approve', 'reject']],

        // رسائل نموذج اتصل بنا — عرض الصندوق، تعليم مقروء/رد، حذف ناعم، استعادة، حذف نهائي
        'inbox' => ['name' => 'صندوق الوارد (اتصل بنا)', 'actions' => ['view', 'update', 'delete', 'restore', 'force_delete']],

        'settings' => ['name' => 'الإعدادات', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force_delete']],
        'third-party' => ['name' => 'إعدادات الطرف الثالث', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete']],

        'reports' => ['name' => 'التقارير', 'actions' => ['view', 'export']],

        'activity_logs' => ['name' => 'سجلات النشاط', 'actions' => ['view', 'delete']],
        'live-streams' => ['name' => 'البث المباشر', 'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete']],
    ];

    protected array $actionDisplayNames = [
        'view' => 'عرض',
        'create' => 'إنشاء',
        'update' => 'تعديل',
        'delete' => 'حذف',
        'restore' => 'استعادة',
        'force_delete' => 'حذف نهائي',
        'force-delete' => 'حذف نهائي',
        'publish' => 'نشر',
        'unpublish' => 'إلغاء نشر',
        'activate' => 'تفعيل',
        'deactivate' => 'تعطيل',
        'approve' => 'موافقة',
        'reject' => 'رفض',
        'export' => 'تصدير',
        'import' => 'استيراد',
        'send' => 'إرسال',
        'sync' => 'مزامنة',
        'run' => 'تشغيل',
        'toggle' => 'تفعيل/إيقاف',
        'invalidate' => 'إبطال الكاش',
    ];

    public function run(): void
    {
        $guard = 'admin';

        foreach ($this->modules as $moduleKey => $moduleData) {
            $group = PermissionGroup::updateOrCreate(
                ['key' => $moduleKey],
                [
                    'name' => $moduleData['name'],
                    'guard_name' => $guard,
                ]
            );

            foreach ($moduleData['actions'] as $action) {
                $name = $moduleKey === 'polls'
                    ? "{$action}_polls"
                    : "{$moduleKey}.{$action}";
                $displayName = $this->actionDisplayNames[$action] ?? $action;

                Permission::updateOrCreate(
                    ['name' => $name, 'guard_name' => $guard],
                    [
                        'display_name' => $displayName,
                        'group_id' => $group->id,
                    ]
                );
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
