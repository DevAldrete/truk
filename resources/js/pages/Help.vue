<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';

const flow = ['Orders', 'Shipments', 'Loads', 'Trips', 'Stops'];
</script>

<template>
    <Head :title="$t('Help')" />

    <div class="mx-auto grid max-w-3xl gap-8 px-6 py-8">
        <header class="grid gap-2">
            <h1 class="text-2xl font-semibold">{{ $t('Help') }}</h1>
            <p class="text-sm text-muted-foreground">
                {{
                    $t(
                        'How the records fit together, and what each word means.',
                    )
                }}
            </p>
        </header>

        <section class="grid gap-3">
            <h2 class="text-sm font-semibold">{{ $t('How it fits') }}</h2>
            <ol class="flex flex-wrap items-center gap-2 text-sm">
                <li
                    v-for="(step, index) in flow"
                    :key="step"
                    class="flex items-center gap-2"
                >
                    <span class="rounded-full border bg-muted/40 px-3 py-1">
                        {{ $t(step) }}
                    </span>
                    <ArrowRight
                        v-if="index < flow.length - 1"
                        class="size-4 text-muted-foreground"
                    />
                </li>
            </ol>
            <p class="text-xs text-muted-foreground">
                {{
                    $t(
                        'An order becomes one or more shipments. Shipments are grouped into a load and executed on a trip with ordered stops.',
                    )
                }}
            </p>
        </section>

        <section class="grid gap-3">
            <h2 class="text-sm font-semibold">{{ $t('Glossary') }}</h2>

            <dl class="divide-y rounded-lg border">
                <div
                    class="grid gap-1 px-4 py-3 sm:grid-cols-[10rem_1fr] sm:gap-4"
                >
                    <dt class="text-sm font-medium">
                        Pedido
                        <span class="text-xs text-muted-foreground">
                            · Order
                        </span>
                    </dt>
                    <dd class="text-sm text-muted-foreground">
                        {{ $t('A commercial request to move goods.') }}
                    </dd>
                </div>

                <div
                    class="grid gap-1 px-4 py-3 sm:grid-cols-[10rem_1fr] sm:gap-4"
                >
                    <dt class="text-sm font-medium">
                        Embarque
                        <span class="text-xs text-muted-foreground">
                            · Shipment
                        </span>
                    </dt>
                    <dd class="text-sm text-muted-foreground">
                        {{
                            $t(
                                'Goods moving from a pickup site to a delivery site. One order can produce several shipments.',
                            )
                        }}
                    </dd>
                </div>

                <div
                    class="grid gap-1 px-4 py-3 sm:grid-cols-[10rem_1fr] sm:gap-4"
                >
                    <dt class="text-sm font-medium">
                        Carga
                        <span class="text-xs text-muted-foreground">
                            · Load
                        </span>
                    </dt>
                    <dd class="text-sm text-muted-foreground">
                        {{ $t('A group of shipments planned together.') }}
                    </dd>
                </div>

                <div
                    class="grid gap-1 px-4 py-3 sm:grid-cols-[10rem_1fr] sm:gap-4"
                >
                    <dt class="text-sm font-medium">
                        Viaje
                        <span class="text-xs text-muted-foreground">
                            · Trip
                        </span>
                    </dt>
                    <dd class="text-sm text-muted-foreground">
                        {{
                            $t('A planned execution using assigned resources.')
                        }}
                    </dd>
                </div>

                <div
                    class="grid gap-1 px-4 py-3 sm:grid-cols-[10rem_1fr] sm:gap-4"
                >
                    <dt class="text-sm font-medium">
                        Parada
                        <span class="text-xs text-muted-foreground">
                            · Stop
                        </span>
                    </dt>
                    <dd class="text-sm text-muted-foreground">
                        {{
                            $t(
                                'A pickup, delivery, or other action within a trip.',
                            )
                        }}
                    </dd>
                </div>

                <div
                    class="grid gap-1 px-4 py-3 sm:grid-cols-[10rem_1fr] sm:gap-4"
                >
                    <dt class="text-sm font-medium">
                        Unidad
                        <span class="text-xs text-muted-foreground">
                            · Vehicle
                        </span>
                    </dt>
                    <dd class="text-sm text-muted-foreground">
                        {{ $t('The tractor unit that pulls a trailer.') }}
                    </dd>
                </div>

                <div
                    class="grid gap-1 px-4 py-3 sm:grid-cols-[10rem_1fr] sm:gap-4"
                >
                    <dt class="text-sm font-medium">
                        Remolque
                        <span class="text-xs text-muted-foreground">
                            · Trailer
                        </span>
                    </dt>
                    <dd class="text-sm text-muted-foreground">
                        {{
                            $t(
                                'The trailer attached to a vehicle; the effective capacity is the lower of the two.',
                            )
                        }}
                    </dd>
                </div>

                <div
                    class="grid gap-1 px-4 py-3 sm:grid-cols-[10rem_1fr] sm:gap-4"
                >
                    <dt class="text-sm font-medium">
                        Conductor
                        <span class="text-xs text-muted-foreground">
                            · Driver
                        </span>
                    </dt>
                    <dd class="text-sm text-muted-foreground">
                        {{ $t('The person who executes a trip.') }}
                    </dd>
                </div>

                <div
                    class="grid gap-1 px-4 py-3 sm:grid-cols-[10rem_1fr] sm:gap-4"
                >
                    <dt class="text-sm font-medium">
                        Ubicación
                        <span class="text-xs text-muted-foreground">
                            · Site
                        </span>
                    </dt>
                    <dd class="text-sm text-muted-foreground">
                        {{ $t('A pickup, delivery, or warehouse address.') }}
                    </dd>
                </div>

                <div
                    class="grid gap-1 px-4 py-3 sm:grid-cols-[10rem_1fr] sm:gap-4"
                >
                    <dt class="text-sm font-medium">
                        Tercero
                        <span class="text-xs text-muted-foreground">
                            · Party
                        </span>
                    </dt>
                    <dd class="text-sm text-muted-foreground">
                        {{
                            $t(
                                'A customer, carrier, or supplier you do business with.',
                            )
                        }}
                    </dd>
                </div>

                <div
                    class="grid gap-1 px-4 py-3 sm:grid-cols-[10rem_1fr] sm:gap-4"
                >
                    <dt class="text-sm font-medium">
                        Paquete
                        <span class="text-xs text-muted-foreground">
                            · Package
                        </span>
                    </dt>
                    <dd class="text-sm text-muted-foreground">
                        {{
                            $t(
                                'Packages are the individual units scanned on the road.',
                            )
                        }}
                    </dd>
                </div>

                <div
                    class="grid gap-1 px-4 py-3 sm:grid-cols-[10rem_1fr] sm:gap-4"
                >
                    <dt class="text-sm font-medium">
                        Acuse / POD
                        <span class="text-xs text-muted-foreground">
                            · Proof of delivery
                        </span>
                    </dt>
                    <dd class="text-sm text-muted-foreground">
                        {{
                            $t(
                                'Evidence, such as a signature or photos, that a delivery happened.',
                            )
                        }}
                    </dd>
                </div>
            </dl>
        </section>
    </div>
</template>
