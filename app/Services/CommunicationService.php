<?php

namespace App\Services;

class CommunicationService
{
    public function delegateSummaryMessage($delegate, $assigned, $votes)
    {
        return implode("\n", [
            "مرحبا {$delegate->name}",
            "",
            "ملخص الأداء:",
            "عدد الناخبين: {$assigned}",
            "عدد المصوتين: {$votes}",
            "",
            "نرجو المتابعة الحثيثة لرفع نسبة الاقتراع"
        ]);
    }

    public function lowTurnoutAlert($delegate)
    {
        return implode("\n", [
            "تنبيه",
            "",
            "نسبة الاقتراع منخفضة في المركز",
            "",
            "يرجى التحرك الفوري والتواصل مع الناخبين"
        ]);
    }

    public function reminderMessage($delegate)
    {
        return implode("\n", [
            "تذكير",
            "",
            "يرجى متابعة الناخبين الذين لم يصوتوا بعد"
        ]);
    }

    public function whatsappLink($phone, $message)
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '970' . substr($phone, 1);
        }

        return "https://wa.me/{$phone}?text=" . rawurlencode($message);
    }
}
