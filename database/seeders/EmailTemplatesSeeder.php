<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Notifications\Models\EmailTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds the six system email templates with sensible defaults in both
 * Arabic and English. Safe to re-run — updateOrCreate is keyed on
 * (key, campaign_type, locale).
 */
final class EmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $items = [];

        // ── campaign.published — generic ──
        $items[] = ['key' => 'campaign.published', 'type' => null, 'locale' => 'en',
            'subject' => 'Voting is now open: {campaign.title}',
            'body'    => "Hello {voter.name},\n\n".
                         "The voting campaign \"{campaign.title}\" is now open on {platform.name}.\n".
                         "Cast your vote before {campaign.end_at}:\n\n{campaign.public_url}\n\nThank you!"];
        $items[] = ['key' => 'campaign.published', 'type' => null, 'locale' => 'ar',
            'subject' => 'فُتح التصويت: {campaign.title}',
            'body'    => "مرحباً {voter.name}،\n\n".
                         "حملة التصويت «{campaign.title}» مفتوحة الآن على {platform.name}.\n".
                         "شارك بصوتك قبل {campaign.end_at}:\n\n{campaign.public_url}\n\nشكراً لك!"];

        // ── campaign.published — per award type ──
        $items[] = ['key' => 'campaign.published', 'type' => CampaignType::TeamOfTheSeason->value, 'locale' => 'en',
            'subject' => '⚽ Build your Team of the Season: {campaign.title}',
            'body'    => "Hello {voter.name},\n\n".
                         "It's time to choose your Team of the Season! Pick your XI before {campaign.end_at}:\n\n".
                         "{campaign.public_url}\n\nGood luck picking the perfect line-up!"];
        $items[] = ['key' => 'campaign.published', 'type' => CampaignType::TeamOfTheSeason->value, 'locale' => 'ar',
            'subject' => '⚽ اختر تشكيلتك: {campaign.title}',
            'body'    => "مرحباً {voter.name}،\n\n".
                         "حان وقت اختيار تشكيلة الموسم! اختر اللاعبين الأحد عشر قبل {campaign.end_at}:\n\n".
                         "{campaign.public_url}\n\nبالتوفيق في اختيار أفضل تشكيلة!"];

        $items[] = ['key' => 'campaign.published', 'type' => CampaignType::IndividualAward->value, 'locale' => 'en',
            'subject' => '🏆 Your vote counts: {campaign.title}',
            'body'    => "Hello {voter.name},\n\nHelp crown this season's winner. Vote now:\n\n{campaign.public_url}\n\nDeadline: {campaign.end_at}"];
        $items[] = ['key' => 'campaign.published', 'type' => CampaignType::IndividualAward->value, 'locale' => 'ar',
            'subject' => '🏆 صوّت الآن: {campaign.title}',
            'body'    => "مرحباً {voter.name}،\n\nساعدنا في اختيار بطل الموسم. صوّت الآن:\n\n{campaign.public_url}\n\nآخر موعد: {campaign.end_at}"];

        // ── campaign.closing_soon ──
        $items[] = ['key' => 'campaign.closing_soon', 'type' => null, 'locale' => 'en',
            'subject' => '⏰ Last chance to vote: {campaign.title}',
            'body'    => "Hello {voter.name},\n\nVoting closes at {campaign.end_at}. If you haven't voted yet, this is your last chance:\n\n{campaign.public_url}"];
        $items[] = ['key' => 'campaign.closing_soon', 'type' => null, 'locale' => 'ar',
            'subject' => '⏰ آخر فرصة للتصويت: {campaign.title}',
            'body'    => "مرحباً {voter.name}،\n\nينتهي التصويت في {campaign.end_at}. إن لم تصوّت بعد، هذه فرصتك الأخيرة:\n\n{campaign.public_url}"];

        // ── campaign.results_announced ──
        $items[] = ['key' => 'campaign.results_announced', 'type' => null, 'locale' => 'en',
            'subject' => '🏅 Results are out: {campaign.title}',
            'body'    => "The results of {campaign.title} are official!\n\nWinners:\n{winners_list}\n\nSee the full ranking:\n{campaign.public_url}"];
        $items[] = ['key' => 'campaign.results_announced', 'type' => null, 'locale' => 'ar',
            'subject' => '🏅 النتائج أُعلنت: {campaign.title}',
            'body'    => "نتائج {campaign.title} رسمية الآن!\n\nالفائزون:\n{winners_list}\n\nاطّلع على الترتيب الكامل:\n{campaign.public_url}"];

        $items[] = ['key' => 'campaign.results_announced', 'type' => CampaignType::TeamOfTheSeason->value, 'locale' => 'en',
            'subject' => '⚽ Team of the Season revealed — {campaign.title}',
            'body'    => "The Team of the Season for {campaign.title} has been revealed!\n\nStarting XI:\n{winners_list}\n\nSee the full pitch:\n{campaign.public_url}"];
        $items[] = ['key' => 'campaign.results_announced', 'type' => CampaignType::TeamOfTheSeason->value, 'locale' => 'ar',
            'subject' => '⚽ كُشفت تشكيلة الموسم — {campaign.title}',
            'body'    => "تشكيلة موسم {campaign.title} أُعلنت!\n\nالتشكيلة الأساسية:\n{winners_list}\n\nشاهد التشكيلة على الملعب:\n{campaign.public_url}"];

        // ── campaign.approved / rejected ──
        $items[] = ['key' => 'campaign.approved', 'type' => null, 'locale' => 'en',
            'subject' => '✓ Your campaign was approved: {campaign.title}',
            'body'    => "Hello {admin.name},\n\nThe committee has approved your campaign \"{campaign.title}\". It will open for voting at the scheduled start time."];
        $items[] = ['key' => 'campaign.approved', 'type' => null, 'locale' => 'ar',
            'subject' => '✓ تم اعتماد حملتك: {campaign.title}',
            'body'    => "مرحباً {admin.name}،\n\nاعتمدت اللجنة حملتك «{campaign.title}». ستُفتح للتصويت في وقت البدء المحدد."];

        $items[] = ['key' => 'campaign.rejected', 'type' => null, 'locale' => 'en',
            'subject' => '✗ Your campaign was rejected: {campaign.title}',
            'body'    => "Hello {admin.name},\n\nThe committee has rejected \"{campaign.title}\".\n\nReason:\n{reason}\n\nYou can edit and resubmit."];
        $items[] = ['key' => 'campaign.rejected', 'type' => null, 'locale' => 'ar',
            'subject' => '✗ رُفضت حملتك: {campaign.title}',
            'body'    => "مرحباً {admin.name}،\n\nرفضت اللجنة «{campaign.title}».\n\nالسبب:\n{reason}\n\nيمكنك تعديلها وإعادة إرسالها."];

        // ── user.invited ──
        $items[] = ['key' => 'user.invited', 'type' => null, 'locale' => 'en',
            'subject' => 'You have been invited to {platform.name}',
            'body'    => "Hello {user.name},\n\nYou have been invited to join {platform.name}. Use the link below to set your password and sign in:\n\n{invite.url}\n\nThis link expires in 60 minutes."];
        $items[] = ['key' => 'user.invited', 'type' => null, 'locale' => 'ar',
            'subject' => 'دعوة للانضمام إلى {platform.name}',
            'body'    => "مرحباً {user.name}،\n\nتمت دعوتك للانضمام إلى {platform.name}. استخدم الرابط التالي لتعيين كلمة المرور وتسجيل الدخول:\n\n{invite.url}\n\nينتهي الرابط خلال 60 دقيقة."];

        foreach ($items as $t) {
            EmailTemplate::updateOrCreate(
                ['key' => $t['key'], 'campaign_type' => $t['type'], 'locale' => $t['locale']],
                ['subject' => $t['subject'], 'body' => $t['body'], 'is_active' => true],
            );
        }
    }
}
