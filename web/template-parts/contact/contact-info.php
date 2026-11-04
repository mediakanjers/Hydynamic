<?php
    $companyname    = get_field('bedrijfsnaam' , 'options');
    $zipcode        = get_field('postcode' , 'options');
    $address        = get_field('adres' , 'options');
    $phonenumber    = get_field('telefoon' , 'options');
    $mobilenumber   = get_field('mobiel' , 'options');
    $mail           = get_field('email' , 'options');
?>

<div class="contact-info">
    <h5>Contact</h5>
    <strong><?php echo esc_html($companyname);?></strong>
    <span><?php echo esc_html($zipcode);?></span>
    <span><?php echo esc_html($address);?></span>
    <a href="tel:<?php echo esc_html($phonenumber);?>"><?php echo esc_html($phonenumber);?></a>
    <a href="tel:<?php echo esc_html($mobilenumber);?>"><?php echo esc_html($mobilenumber);?></a>
    <a href="mailto:<?php echo esc_html($mail);?>"><?php echo esc_html($mail);?></a>
</div>