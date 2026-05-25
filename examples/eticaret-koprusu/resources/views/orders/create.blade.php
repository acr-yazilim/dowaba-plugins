@extends('layouts.app')
@section('title', 'Yeni Sipariş')

@section('content')
    <h1 style="margin: 0 0 20px; font-size: 24px;">Yeni Sipariş</h1>

    <form method="POST" action="{{ route('orders.store') }}">
        @csrf

        <div class="card" style="max-width: 720px;">
            <h2>Müşteri ve Teslimat</h2>

            <div class="form-row">
                <label for="customer_id">Müşteri</label>
                <select name="customer_id" id="customer_id" required>
                    <option value="">— Müşteri seç —</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" data-address="{{ $c->address }}">{{ $c->name }} ({{ $c->phone }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-row">
                <label for="shipping_address">Teslimat Adresi</label>
                <textarea name="shipping_address" id="shipping_address" rows="3" required></textarea>
            </div>

            <div class="form-row">
                <label for="notes">Notlar (opsiyonel)</label>
                <textarea name="notes" id="notes" rows="2"></textarea>
            </div>
        </div>

        <div class="card" style="max-width: 720px;">
            <h2>Ürünler</h2>
            <div id="items">
                <div class="item-row" style="display: grid; grid-template-columns: 1fr 100px 30px; gap: 8px; margin-bottom: 8px; align-items: end;">
                    <div class="form-row" style="margin: 0;">
                        <label>Ürün</label>
                        <select name="items[0][product_id]" required>
                            <option value="">— Ürün seç —</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} — {{ number_format((float) $p->price, 0, ',', '.') }} ₺ (stok: {{ $p->stock }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row" style="margin: 0;">
                        <label>Adet</label>
                        <input type="number" name="items[0][quantity]" min="1" value="1" required>
                    </div>
                    <div></div>
                </div>
            </div>

            <button type="button" class="btn btn-ghost" onclick="addItem()">+ Ürün Ekle</button>
        </div>

        @if($errors->any())
            <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 14px; max-width: 720px;">
                @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
            </div>
        @endif

        <div style="display: flex; gap: 8px; max-width: 720px;">
            <button type="submit" class="btn">Sipariş Oluştur (+ WhatsApp Bildirim)</button>
            <a class="btn btn-ghost" href="{{ route('orders.index') }}">İptal</a>
        </div>
    </form>

    <p style="margin-top: 14px; color: #6b7280; font-size: 13px; max-width: 720px;">
        ℹ️ Sipariş kaydedildiğinde müşteriye <strong>"order_placed"</strong> WhatsApp template'i gider.
        Sonraki status değişimlerinde (paid/shipped/delivered) ilgili template otomatik tetiklenir.
    </p>

    <script>
        // Müşteri seçilince adresi otomatik doldur
        document.getElementById('customer_id').addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const addr = opt.getAttribute('data-address');
            if (addr) document.getElementById('shipping_address').value = addr;
        });

        let itemIndex = 1;
        function addItem() {
            const productOptions = `@foreach($products as $p)<option value="{{ $p->id }}">{{ addslashes($p->name) }} — {{ number_format((float) $p->price, 0, ',', '.') }} ₺ (stok: {{ $p->stock }})</option>@endforeach`;
            const row = document.createElement('div');
            row.className = 'item-row';
            row.style.cssText = 'display: grid; grid-template-columns: 1fr 100px 30px; gap: 8px; margin-bottom: 8px; align-items: end;';
            row.innerHTML = `
                <div class="form-row" style="margin: 0;">
                    <label>Ürün</label>
                    <select name="items[${itemIndex}][product_id]" required>
                        <option value="">— Ürün seç —</option>
                        ${productOptions}
                    </select>
                </div>
                <div class="form-row" style="margin: 0;">
                    <label>Adet</label>
                    <input type="number" name="items[${itemIndex}][quantity]" min="1" value="1" required>
                </div>
                <button type="button" onclick="this.parentElement.remove()" style="background: #dc2626; color: #fff; border: 0; border-radius: 6px; padding: 8px; cursor: pointer;">×</button>
            `;
            document.getElementById('items').appendChild(row);
            itemIndex++;
        }
    </script>
@endsection
