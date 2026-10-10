@if (config('shop.captcha.driver') === 'turnstile' && config('shop.captcha.turnstile_site_key'))
    <div class="cf-turnstile" data-sitekey="{{ config('shop.captcha.turnstile_site_key') }}" data-theme="dark" data-language="de"></div>
    @once<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endonce
@endif
<input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
