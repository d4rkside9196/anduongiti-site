<?php

namespace App\Filament\Pages;

use App\Authorization\AdminPermissions;
use App\Models\Setting;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Navigation;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Settings';

    protected static string|UnitEnum|null $navigationGroup = '';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.manage-settings';

    // /admin/settings instead of the default /admin/manage-settings.
    protected static ?string $slug = 'settings';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && AdminPermissions::allows($user, AdminPermissions::SETTINGS_WRITE);
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->form->fill($this->settingsState());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Public navigation')
                    ->description('Choose and reorder enabled public links. URLs are fixed by the application route allowlist.')
                    ->components([
                        Repeater::make('navigation')
                            ->schema([
                                Select::make('key')
                                    ->label('Navigation item')
                                    ->options(fn (): array => collect(Navigation::allowedKeys())
                                        ->mapWithKeys(fn (string $key): array => [$key => __("site.nav.{$key}")])
                                        ->all())
                                    ->required()
                                    ->in(Navigation::allowedKeys())
                                    ->distinct(),
                            ])
                            ->reorderable()
                            ->addActionLabel('Add navigation item')
                            ->maxItems(count(Navigation::allowedKeys()))
                            ->default(array_map(fn (string $key): array => ['key' => $key], Navigation::allowedKeys()))
                            ->columnSpanFull(),
                    ]),
                Section::make('Contact')
                    ->description('Public contact details shown in the footer, floating widget and contact page.')
                    ->columns(2)
                    ->components([
                        TextInput::make('contact.email')
                            ->label('Contact email')
                            ->email()
                            ->required(),
                        TextInput::make('contact.phone')
                            ->label('Phone')
                            ->maxLength(50),
                        TextInput::make('contact.zalo')
                            ->label('Zalo number')
                            ->helperText('Phone-format, digits only used for zalo.me links, e.g. +84 915 663 683')
                            ->maxLength(50),
                        TextInput::make('contact.whatsapp')
                            ->label('WhatsApp number')
                            ->helperText('Phone-format, digits only used for wa.me links, e.g. +84 915 663 683')
                            ->maxLength(50),
                        Textarea::make('contact.address_en')
                            ->label('Address (EN)')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('contact.address_vi')
                            ->label('Address (VI)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Social links')
                    ->description('Rendered as plain links (footer, contact page) — no embedded widgets.')
                    ->columns(2)
                    ->components([
                        TextInput::make('social.facebook')
                            ->label('Facebook URL')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social.shopee')
                            ->label('Shopee shop URL')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social.alibaba')
                            ->label('Alibaba supplier URL')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social.linkedin')
                            ->label('LinkedIn company URL')
                            ->url()
                            ->maxLength(255),
                    ]),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $before = $this->settingsState();
        $data = $this->form->getState();
        $changedKeys = $this->changedSettingKeys($before, $data);

        DB::transaction(function () use ($data): void {
            Setting::write('navigation', array_values(array_filter(
                array_column($data['navigation'] ?? [], 'key'),
                fn (mixed $key): bool => is_string($key),
            )));

            // Merge into the stored rows so keys not exposed on this page
            // (e.g. legacy contact.linkedin consumers) are preserved.
            $contact = Setting::read('contact', []);
            Setting::write('contact', array_merge($contact, Arr::only(
                $data['contact'] ?? [],
                ['email', 'phone', 'zalo', 'whatsapp', 'address_en', 'address_vi'],
            )));

            $social = Setting::read('social', []);
            Setting::write('social', array_merge($social, Arr::only(
                $data['social'] ?? [],
                ['facebook', 'shopee', 'alibaba', 'linkedin'],
            )));
        });

        app(ActivityLogger::class)->record('settings.saved', properties: [
            'changed_keys' => $changedKeys,
        ]);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();

        $this->form->fill($this->settingsState());
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function changedSettingKeys(array $before, array $data): array
    {
        $changed = [];
        $beforeNavigation = array_column($before['navigation'] ?? [], 'key');
        $afterNavigation = array_column($data['navigation'] ?? [], 'key');

        if ($beforeNavigation !== $afterNavigation) {
            $changed[] = 'navigation';
        }

        foreach (['contact', 'social'] as $group) {
            foreach (array_keys($before[$group] ?? []) as $key) {
                if ((string) ($before[$group][$key] ?? '') !== (string) ($data[$group][$key] ?? '')) {
                    $changed[] = "{$group}.{$key}";
                }
            }
        }

        return $changed;
    }

    /**
     * Flatten the two settings rows into the nested form state
     * (contact.* / social.*), blanking missing keys for the inputs.
     */
    protected function settingsState(): array
    {
        $contact = Setting::read('contact', []);
        $social = Setting::read('social', []);

        return [
            'navigation' => array_map(
                fn (string $key): array => ['key' => $key],
                array_column(Navigation::items(), 'key'),
            ),
            'contact' => array_merge([
                'email' => $contact['email'] ?? '',
                'phone' => $contact['phone'] ?? '',
                'zalo' => $contact['zalo'] ?? '',
                'whatsapp' => $contact['whatsapp'] ?? '',
                'address_en' => $contact['address_en'] ?? '',
                'address_vi' => $contact['address_vi'] ?? '',
            ]),
            'social' => array_merge([
                'facebook' => $social['facebook'] ?? '',
                'shopee' => $social['shopee'] ?? '',
                'alibaba' => $social['alibaba'] ?? '',
                'linkedin' => $social['linkedin'] ?? ($contact['linkedin'] ?? ''),
            ]),
        ];
    }
}
