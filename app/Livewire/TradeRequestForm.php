<?php

namespace App\Livewire;

use App\Enums\ItemCondition;
use App\Enums\TradeRequestType;
use App\Models\Manufacturer;
use App\Rules\Captcha;
use App\Rules\Honeypot;
use App\Services\Trade\TradeRequestService;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Formular für den egun-Verkaufsservice (consignment) und den Ankauf (buyback). */
class TradeRequestForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $type = 'consignment';

    public string $name = '';

    public string $street = '';

    public string $postal_code = '';

    public string $city = '';

    public string $phone = '';

    public string $email = '';

    public string $weapon_type = '';

    public ?string $manufacturer_id = null;

    public string $manufacturer_name = '';

    public string $model = '';

    public string $caliber = '';

    public string $condition_code = 'very_good';

    public string $desired_price = '';

    public string $description = '';

    public array $photos = [];

    public bool $privacy = false;

    public string $website = '';

    public string $captcha = '';

    public ?string $reference = null;

    public function mount(string $type = 'consignment'): void
    {
        $this->type = TradeRequestType::from($type)->value;
        if ($user = Auth::user()) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = (string) $user->phone;
            if ($a = $user->addresses()->where('type', 'billing')->first()) {
                $this->street = "{$a->street} {$a->house_number}";
                $this->postal_code = $a->postal_code;
                $this->city = $a->city;
            }
        }
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function submit(TradeRequestService $service): void
    {
        $consignment = $this->type === 'consignment';
        $max = config('shop.trade_requests.max_photos');

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'street' => [$consignment ? 'required' : 'nullable', 'string', 'max:200'],
            'postal_code' => [$consignment ? 'required' : 'nullable', 'regex:/^\d{5}$/'],
            'city' => [$consignment ? 'required' : 'nullable', 'string', 'max:100'],
            'phone' => [$consignment ? 'required' : 'nullable', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255'],
            'weapon_type' => [$consignment ? 'required' : 'nullable', 'string', 'max:80'],
            'manufacturer_id' => ['nullable', 'exists:manufacturers,id'],
            'manufacturer_name' => ['required_without:manufacturer_id', 'nullable', 'string', 'max:120'],
            'model' => ['required', 'string', 'max:160'],
            'caliber' => [$consignment ? 'nullable' : 'required', 'string', 'max:80'],
            'condition_code' => ['required', Rule::enum(ItemCondition::class)],
            'desired_price' => ['nullable', 'regex:/^\d{1,7}([.,]\d{1,2})?$/'],
            'description' => ['nullable', 'string', 'max:3000'],
            'photos' => [$consignment ? 'required' : 'nullable', 'array', "max:$max"],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('shop.trade_requests.max_photo_kb')],
            'privacy' => ['accepted'],
            'website' => [new Honeypot],
            'captcha' => [new Captcha],
        ], [
            'photos.required' => 'Bitte laden Sie mindestens ein Foto hoch.',
            'photos.max' => "Maximal $max Fotos.",
            'manufacturer_name.required_without' => 'Bitte Hersteller auswählen oder eintragen.',
            'privacy.accepted' => 'Bitte bestätigen Sie die Datenschutzhinweise.',
        ]);

        if (! RateLimiter::attempt('trade:'.request()->ip(), 5, fn () => true, 3600)) {
            $this->addError('model', 'Zu viele Anfragen. Bitte versuchen Sie es später erneut.');

            return;
        }

        $data['desired_price'] = Money::toCents($data['desired_price'] ?? null);
        $request = $service->create(TradeRequestType::from($this->type), $data, $this->photos, Auth::id());
        $this->reference = $request->reference;
    }

    public function render()
    {
        return view('livewire.trade-request-form', [
            'manufacturers' => Manufacturer::orderBy('name')->get(['id', 'name']),
            'conditions' => ItemCondition::cases(),
        ]);
    }
}
