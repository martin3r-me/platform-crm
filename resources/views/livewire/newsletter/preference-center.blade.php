<div style="max-width: 480px; width: 100%;">
    <div style="background: #ffffff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 40px; width: 100%;">

        {{-- Header --}}
        <div style="text-align: center; margin-bottom: 32px;">
            <h1 style="font-size: 20px; font-weight: 600; color: #111827; margin-bottom: 8px;">Newsletter-Einstellungen</h1>
            <p style="font-size: 13px; color: #9ca3af;">{{ $email }}</p>
        </div>

        {{-- Flash Messages --}}
        @if(session('message'))
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 12px 16px; margin-bottom: 24px; font-size: 13px; color: #065f46;">
                {{ session('message') }}
            </div>
        @endif

        @if($isGloballyUnsubscribed)
            {{-- Global Unsubscribe Notice --}}
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 24px;">
                <p style="font-size: 14px; color: #991b1b; margin-bottom: 16px;">
                    Sie sind derzeit von allen Newslettern abgemeldet.
                </p>
                <button
                    wire:click="resubscribeGlobal"
                    wire:loading.attr="disabled"
                    style="display: inline-block; padding: 10px 24px; background-color: #10b981; color: #ffffff; font-size: 13px; font-weight: 600; border: none; border-radius: 8px; cursor: pointer;"
                >
                    Erneut anmelden
                </button>
            </div>
        @else
            {{-- List Subscriptions --}}
            @if(count($listSubscriptions) === 0)
                <div style="text-align: center; padding: 24px 0;">
                    <p style="font-size: 14px; color: #6b7280;">Keine Newsletter-Listen verfügbar.</p>
                </div>
            @else
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                    @foreach($listSubscriptions as $index => $subscription)
                        <div style="padding: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px;{{ $index > 0 ? ' border-top: 1px solid #e5e7eb;' : '' }}">
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
                                    @if($subscription['color'])
                                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: {{ $subscription['color'] }}; flex-shrink: 0;"></span>
                                    @endif
                                    <span style="font-size: 14px; font-weight: 500; color: #111827;">{{ $subscription['name'] }}</span>
                                </div>
                                @if($subscription['description'])
                                    <p style="font-size: 12px; color: #9ca3af; margin-top: 2px;">{{ $subscription['description'] }}</p>
                                @endif
                                @if($subscription['status'] === 'pending_doi')
                                    <span style="display: inline-block; margin-top: 4px; font-size: 11px; padding: 2px 8px; border-radius: 9999px; background: #fef3c7; color: #92400e;">Bestätigung ausstehend</span>
                                @endif
                            </div>
                            <div style="flex-shrink: 0;">
                                @if($subscription['status'] === 'pending_doi')
                                    <span style="display: inline-block; width: 44px; height: 24px; border-radius: 9999px; background: #fbbf24; position: relative;">
                                        <span style="position: absolute; top: 2px; left: 22px; width: 20px; height: 20px; border-radius: 50%; background: #fff; transition: all 0.2s;"></span>
                                    </span>
                                @elseif($subscription['status'] === 'subscribed')
                                    <button
                                        wire:click="toggleList({{ $subscription['list_id'] }})"
                                        wire:loading.attr="disabled"
                                        style="display: inline-block; width: 44px; height: 24px; border-radius: 9999px; background: #10b981; position: relative; border: none; cursor: pointer;"
                                        title="Abmelden"
                                    >
                                        <span style="position: absolute; top: 2px; left: 22px; width: 20px; height: 20px; border-radius: 50%; background: #fff; transition: all 0.2s;"></span>
                                    </button>
                                @else
                                    <button
                                        wire:click="toggleList({{ $subscription['list_id'] }})"
                                        wire:loading.attr="disabled"
                                        style="display: inline-block; width: 44px; height: 24px; border-radius: 9999px; background: #d1d5db; position: relative; border: none; cursor: pointer;"
                                        title="Anmelden"
                                    >
                                        <span style="position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; border-radius: 50%; background: #fff; transition: all 0.2s;"></span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Global Unsubscribe Button --}}
                <div style="text-align: center; margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
                    <button
                        wire:click="globalUnsubscribe"
                        wire:loading.attr="disabled"
                        wire:confirm="Möchten Sie sich wirklich von allen Newslettern abmelden?"
                        style="font-size: 12px; color: #9ca3af; background: none; border: none; cursor: pointer; text-decoration: underline;"
                    >
                        Von allen Newslettern abmelden
                    </button>
                </div>
            @endif
        @endif
    </div>
</div>
