@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
            <table cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
                <tr>
                    <td align="center" style="padding: 0 0 20px 0;">
                        <img src="{{ config('app.url') }}/icon.png" alt="SIMANTIK" width="48" height="48" style="display: block; width: 48px; height: 48px; margin: 0 auto;" />
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding: 0;">
                        <strong style="font-size: 28px; color: #2563eb; font-weight: 700; display: block; margin: 0; padding: 0; line-height: 1.2;">{!! $slot !!}</strong>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding: 8px 0 0 0;">
                        <span style="font-size: 13px; color: #71717a; line-height: 1.5; font-weight: 400; display: block;">Sistem Manajemen Petugas dan Administrasi Kegiatan Statistik</span>
                    </td>
                </tr>
            </table>
        </a>
    </td>
</tr>