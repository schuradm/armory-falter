<x-layouts.account active="orders">
    <h1 class="font-display text-2xl font-extrabold uppercase">Bestellungen</h1>
    @include('account.partials.order-table', ['orders' => $orders])
    <div class="mt-6">{{ $orders->links() }}</div>
</x-layouts.account>
