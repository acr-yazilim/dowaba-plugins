@props([])
<form action="{{ $action }}" method="{{ $method }}" class="{{ $class }}" data-dowaba="contact-create-form" data-site-id="{{ $siteId }}">
    @csrf
    <input type="hidden" name="site_id" value="{{ $siteId }}">
    <div style="display: grid; gap: 10px;">
        <label style="display: grid; gap: 4px;">
            <span style="font-size: 13px; color: #4b5563;">Ad Soyad</span>
            <input type="text" name="name" required style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
        </label>
        <label style="display: grid; gap: 4px;">
            <span style="font-size: 13px; color: #4b5563;">Telefon</span>
            <input type="tel" name="phone" placeholder="+90..." style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
        </label>
        <label style="display: grid; gap: 4px;">
            <span style="font-size: 13px; color: #4b5563;">E-posta</span>
            <input type="email" name="email" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
        </label>
        <button type="submit" style="padding: 10px 18px; background: #111827; color: #fff; border: 0; border-radius: 8px; font-weight: 500; cursor: pointer;">
            {{ $submitLabel }}
        </button>
    </div>
</form>
