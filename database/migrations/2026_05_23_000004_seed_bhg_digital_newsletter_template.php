<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\UuidV7;

return new class extends Migration
{
    public function up(): void
    {
        // Only seed if the team exists and no templates exist yet
        $team = DB::table('teams')->where('name', 'BHG.DIGITAL')->first();
        if (!$team) {
            return;
        }

        if (DB::table('comms_newsletter_templates')->where('team_id', $team->id)->exists()) {
            return;
        }

        $htmlBody = <<<'HTML'
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BHG.DIGITAL Newsletter</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;">

  <!-- Wrapper -->
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f4f4f5;">
    <tr><td align="center" style="padding:40px 16px;">

      <!-- Container -->
      <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;width:100%;">

        <!-- Header -->
        <tr><td style="background-color:#18181b;border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
          <h1 style="margin:0;font-size:28px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">BHG<span style="color:#ff7a59;">.</span>DIGITAL</h1>
          <p style="margin:8px 0 0;font-size:13px;color:#a1a1aa;letter-spacing:1px;text-transform:uppercase;">Newsletter</p>
        </td></tr>

        <!-- Hero -->
        <tr><td style="background-color:#ffffff;padding:40px 40px 32px;">
          <h2 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#18181b;line-height:1.3;">Titel des Newsletters</h2>
          <p style="margin:0;font-size:15px;line-height:1.7;color:#52525b;">Einleitungstext — beschreiben Sie hier kurz, worum es in diesem Newsletter geht.</p>
        </td></tr>

        <!-- Divider -->
        <tr><td style="background-color:#ffffff;padding:0 40px;"><div style="height:1px;background-color:#e4e4e7;"></div></td></tr>

        <!-- Feature Block 1 -->
        <tr><td style="background-color:#ffffff;padding:32px 40px;">
          <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
              <td width="48" valign="top" style="padding-right:16px;">
                <div style="width:48px;height:48px;border-radius:10px;background-color:#fff7ed;text-align:center;line-height:48px;font-size:22px;">&#128640;</div>
              </td>
              <td valign="top">
                <h3 style="margin:0 0 8px;font-size:16px;font-weight:600;color:#18181b;">Thema 1</h3>
                <p style="margin:0;font-size:14px;line-height:1.6;color:#71717a;">Beschreibung des ersten Themas. Ersetzen Sie diesen Text mit Ihrem Inhalt.</p>
              </td>
            </tr>
          </table>
        </td></tr>

        <!-- Feature Block 2 -->
        <tr><td style="background-color:#ffffff;padding:0 40px 32px;">
          <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
              <td width="48" valign="top" style="padding-right:16px;">
                <div style="width:48px;height:48px;border-radius:10px;background-color:#eff6ff;text-align:center;line-height:48px;font-size:22px;">&#128200;</div>
              </td>
              <td valign="top">
                <h3 style="margin:0 0 8px;font-size:16px;font-weight:600;color:#18181b;">Thema 2</h3>
                <p style="margin:0;font-size:14px;line-height:1.6;color:#71717a;">Beschreibung des zweiten Themas. Ersetzen Sie diesen Text mit Ihrem Inhalt.</p>
              </td>
            </tr>
          </table>
        </td></tr>

        <!-- Feature Block 3 -->
        <tr><td style="background-color:#ffffff;padding:0 40px 32px;">
          <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
              <td width="48" valign="top" style="padding-right:16px;">
                <div style="width:48px;height:48px;border-radius:10px;background-color:#f0fdf4;text-align:center;line-height:48px;font-size:22px;">&#128161;</div>
              </td>
              <td valign="top">
                <h3 style="margin:0 0 8px;font-size:16px;font-weight:600;color:#18181b;">Thema 3</h3>
                <p style="margin:0;font-size:14px;line-height:1.6;color:#71717a;">Beschreibung des dritten Themas. Ersetzen Sie diesen Text mit Ihrem Inhalt.</p>
              </td>
            </tr>
          </table>
        </td></tr>

        <!-- CTA -->
        <tr><td style="background-color:#ffffff;padding:8px 40px 40px;text-align:center;">
          <a href="https://office.bhgdigital.de" style="display:inline-block;padding:14px 32px;background-color:#ff7a59;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;border-radius:8px;letter-spacing:0.3px;">Zum Dashboard &rarr;</a>
        </td></tr>

        <!-- Footer -->
        <tr><td style="background-color:#fafafa;border-radius:0 0 12px 12px;padding:28px 40px;border-top:1px solid #e4e4e7;">
          <p style="margin:0 0 4px;font-size:12px;color:#a1a1aa;text-align:center;">BHG.DIGITAL &middot; Agentur f&uuml;r digitale L&ouml;sungen</p>
          <p style="margin:0;font-size:11px;color:#d4d4d8;text-align:center;">Sie erhalten diese E-Mail, weil Sie sich f&uuml;r unseren Newsletter angemeldet haben.</p>
        </td></tr>

      </table>
      <!-- /Container -->

    </td></tr>
  </table>
  <!-- /Wrapper -->

</body>
</html>
HTML;

        $textBody = <<<'TEXT'
BHG.DIGITAL — Newsletter
====================================

Titel des Newsletters

Einleitungstext — beschreiben Sie hier kurz, worum es in diesem Newsletter geht.

---

Thema 1
Beschreibung des ersten Themas. Ersetzen Sie diesen Text mit Ihrem Inhalt.

Thema 2
Beschreibung des zweiten Themas. Ersetzen Sie diesen Text mit Ihrem Inhalt.

Thema 3
Beschreibung des dritten Themas. Ersetzen Sie diesen Text mit Ihrem Inhalt.

---

Zum Dashboard: https://office.bhgdigital.de

---
BHG.DIGITAL - Agentur für digitale Lösungen
Sie erhalten diese E-Mail, weil Sie sich für unseren Newsletter angemeldet haben.
TEXT;

        DB::table('comms_newsletter_templates')->insert([
            'uuid' => UuidV7::generate(),
            'team_id' => $team->id,
            'created_by_user_id' => DB::table('users')->where('email', 'like', '%erren%bhgdigital%')->value('id'),
            'name' => 'BHG Standard-Newsletter',
            'description' => 'Standard-Vorlage für BHG.DIGITAL Newsletter mit Header, 3 Feature-Blöcken, CTA-Button und Footer in den BHG-Markenfarben.',
            'category' => 'Marketing',
            'html_body' => $htmlBody,
            'text_body' => $textBody,
            'default_subject' => 'Neuigkeiten von BHG.DIGITAL',
            'default_preheader' => 'Was gibt es Neues bei uns?',
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $team = DB::table('teams')->where('name', 'BHG.DIGITAL')->first();
        if ($team) {
            DB::table('comms_newsletter_templates')
                ->where('team_id', $team->id)
                ->where('name', 'BHG Standard-Newsletter')
                ->delete();
        }
    }
};
