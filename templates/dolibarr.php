<table cellpadding="0" cellspacing="0" border="0" style="font-family: 'Titillium Web', Arial, sans-serif; font-size: 12px; color: #333; border-collapse: collapse;">
  <tr>
    <td style="padding: 10px 15px; background: linear-gradient(135deg, #8a4dfd 0%, #6366f1 100%); border-radius: 8px 8px 0 0;">
      <table cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="vertical-align: middle; padding-right: 12px;">
            <img src="<?= $company['logo'] ?>" alt="" width="45" height="45" style="display: block; border-radius: 10px; border: 2px solid rgba(255,255,255,0.3);">
          </td>
          <td style="vertical-align: middle;">
            <span style="font-size: 15px; font-weight: 700; color: #ffffff; display: block; line-height: 1.2;">👤 <?= $name ?></span>
            <?php if ($job): ?>
            <span style="font-size: 11px; color: rgba(255,255,255,0.9); font-weight: 500; display: block; margin-top: 2px;">💼 <?= $job ?></span>
            <?php endif; ?>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding: 12px 15px; background: #f8f9fa; border: 1px solid #e9ecef; border-top: none; border-radius: 0 0 8px 8px;">
      <table cellpadding="0" cellspacing="0" border="0" style="font-size: 11px;">
        <tr>
          <td style="padding-bottom: 4px;">
            <span style="color: #6c757d;">✉️ Email :</span>
            <a href="mailto:<?= $email ?>" style="color: #333; text-decoration: none; font-weight: 500; margin-left: 5px;"><?= $email ?></a>
          </td>
        </tr>
        <tr>
          <td style="padding-bottom: 4px;">
            <span style="color: #6c757d;">🌐 Web :</span>
            <a href="<?= $company['website'] ?>" style="color: #8a4dfd; text-decoration: none; font-weight: 600; margin-left: 5px;"><?= $company['domain'] ?></a>
          </td>
        </tr>
        <tr>
          <td>
            <span style="color: #6c757d;">📍 Adresse :</span>
            <span style="color: #333; margin-left: 5px;"><?= $company['address'] ?></span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
