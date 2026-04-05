<table cellpadding="0" cellspacing="0" border="0" style="font-family: 'Titillium Web', Arial, Helvetica, sans-serif; font-size: 13px; color: #333333; line-height: 1.5; max-width: 500px;">
  <tbody>
    <tr>
      <td style="vertical-align: middle; padding-right: 15px; border-right: 3px solid #8a4dfd;">
        <img src="<?= $company['logo'] ?>" alt="<?= $company['name'] ?>" width="70" height="70" style="display: block; border-radius: 8px;">
      </td>
      <td style="vertical-align: middle; padding-left: 15px;">
        <!-- Name -->
        <p style="margin: 0 0 2px 0; font-size: 16px; font-weight: bold; color: #1a1a1a;"><?= $name ?></p>
        <?php if ($job): ?>
        <p style="margin: 0 0 8px 0; font-size: 13px; color: #8a4dfd; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;"><?= $job ?></p>
        <?php else: ?>
        <p style="margin: 0 0 8px 0;"></p>
        <?php endif; ?>
        
        <!-- Contact line -->
        <p style="margin: 0; font-size: 12px; color: #555555;">
          <a href="mailto:<?= $email ?>" style="color: #555555; text-decoration: none;"><?= $email ?></a>
          <span style="color: #ccc; margin: 0 8px;">|</span>
          <a href="<?= $company['website'] ?>" style="color: #8a4dfd; text-decoration: none; font-weight: 600;"><?= $company['domain'] ?></a>
        </p>
      </td>
    </tr>
  </tbody>
</table>