<table cellpadding="0" cellspacing="0" border="0" style="font-family: 'Titillium Web', Segoe UI, Tahoma, Arial, sans-serif; font-size: 13px; color: #333333; line-height: 1.5; max-width: 500px;">
  <tr>
    <td valign="middle" style="padding-right: 15px; vertical-align: middle; border-right: 3px solid #8a4dfd;">
      <img src="<?= $company['logo'] ?>" alt="<?= $company['name'] ?>" width="70" height="70" style="display: block; border-radius: 8px;">
    </td>
    <td valign="middle" style="padding-left: 15px; vertical-align: middle;">
      <p style="margin: 0 0 2px 0; font-size: 16px; font-weight: bold; color: #1a1a1a;">👤 <?= $name ?></p>
      <?php if ($job): ?>
      <p style="margin: 0 0 8px 0; font-size: 13px; color: #8a4dfd; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">💼 <?= $job ?></p>
      <?php else: ?>
      <p style="margin: 0 0 8px 0;"></p>
      <?php endif; ?>
      <p style="margin: 0; font-size: 12px; color: #555555;">
        ✉️ <a href="mailto:<?= $email ?>" style="color: #555555; text-decoration: none;"><?= $email ?></a>
        <span style="color: #ccc; margin: 0 8px;">|</span>
        🌐 <a href="<?= $company['website'] ?>" style="color: #8a4dfd; text-decoration: none; font-weight: 600;"><?= $company['domain'] ?></a>
      </p>
    </td>
  </tr>
</table>
