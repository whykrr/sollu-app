<template>
    <Card title="Theme Color">
        <div>Every color has a light and darker shade</div>
        <div class="grid grid-cols-5 gap-4">
            <div class="col-span-1">
                <div class="flex flex-col gap-1">
                    <div class="w-full pt-[75%] bg-main rounded-lg"></div>
                    <div class="bold">Main Color</div>
                    <div>Hex : rgb(0, 74, 173)</div>
                    <div>Class : .bg-main</div>
                </div>
            </div>
            <div class="col-span-1">
                <div class="flex flex-col gap-1">
                    <div class="w-full pt-[75%] bg-secondary rounded-lg"></div>
                    <div class="bold">Secondary Color</div>
                    <div>Hex : rgb(93, 224, 230)</div>
                    <div>Class : .bg-secondary</div>
                </div>
            </div>
            <div class="col-span-1">
                <div class="flex flex-col gap-1">
                    <div class="w-full pt-[75%] bg-clay rounded-lg"></div>
                    <div class="bold">Clay Color</div>
                    <div>Hex : rgb(173, 91, 0)</div>
                    <div>Class : .bg-clay</div>
                </div>
            </div>
            <div class="col-span-1">
                <div class="flex flex-col gap-1">
                    <div class="w-full pt-[75%] bg-teal rounded-lg"></div>
                    <div class="bold">Teal Color</div>
                    <div>Hex : rgb(0, 114, 173)</div>
                    <div>Class : .bg-teal</div>
                </div>
            </div>
            <div class="col-span-1">
                <div class="flex flex-col gap-1">
                    <div class="w-full pt-[75%] bg-turquoise rounded-lg"></div>
                    <div class="bold">Turquoise Color</div>
                    <div>Hex : rgb(54, 199, 201)</div>
                    <div>Class : .bg-turquoise</div>
                </div>
            </div>
            <div class="col-span-1">
                <div class="flex flex-col gap-1">
                    <div class="w-full pt-[75%] bg-indigo rounded-lg"></div>
                    <div class="bold">Indigo Color</div>
                    <div>Hex : rgb(0, 42, 173)</div>
                    <div>Class : .bg-indigo</div>
                </div>
            </div>
            <div class="col-span-1">
                <div class="flex flex-col gap-1">
                    <div class="w-full pt-[75%] bg-neutral rounded-lg"></div>
                    <div class="bold">Neutral Color</div>
                    <div>Hex : rgb(224, 224, 224)</div>
                    <div>Class : .bg-neutral</div>
                </div>
            </div>
            <div class="col-span-1">
                <div class="flex flex-col gap-1">
                    <div class="w-full pt-[75%] bg-purple rounded-lg"></div>
                    <div class="bold">Purple Color</div>
                    <div>Hex : rgb(106, 13, 173)</div>
                    <div>Class : .bg-purple</div>
                </div>
            </div>
            <div class="col-span-1">
                <div class="flex flex-col gap-1">
                    <div class="w-full pt-[75%] bg-pink rounded-lg"></div>
                    <div class="bold">Pink Color</div>
                    <div>Hex : rgb(255, 111, 145)</div>
                    <div>Class : .bg-pink</div>
                </div>
            </div>
            <div class="col-span-1">
                <div class="flex flex-col gap-1">
                    <div class="w-full pt-[75%] bg-brown rounded-lg"></div>
                    <div class="bold">Brown Color</div>
                    <div>Hex : rgb(123, 75, 58)</div>
                    <div>Class : .bg-brown</div>
                </div>
            </div>
            <div class="col-span-1">
                <div class="flex flex-col gap-1">
                    <div class="w-full pt-[75%] bg-gray rounded-lg"></div>
                    <div class="bold">Gray Color</div>
                    <div>Hex : rgb(176, 176, 176)</div>
                    <div>Class : .bg-gray</div>
                </div>
            </div>
            <div class="col-span-1">
                <div class="flex flex-col gap-1">
                    <div class="w-full pt-[75%] bg-danger rounded-lg"></div>
                    <div class="bold">Danger Color</div>
                    <div>Hex : rgb(230, 57, 70)</div>
                    <div>Class : .bg-danger</div>
                </div>
            </div>
            <div class="col-span-1">
                <div class="flex flex-col gap-1">
                    <div class="w-full pt-[75%] bg-warning rounded-lg"></div>
                    <div class="bold">Warning Color</div>
                    <div>Hex : rgb(255, 183, 3)</div>
                    <div>Class : .bg-warning</div>
                </div>
            </div>
            <div class="col-span-1">
                <div class="flex flex-col gap-1">
                    <div class="w-full pt-[75%] bg-success rounded-lg"></div>
                    <div class="bold">Success Color</div>
                    <div>Hex : rgb(42, 157, 143)</div>
                    <div>Class : .bg-success</div>
                </div>
            </div>
            <div class="col-span-1">
                <div class="flex flex-col gap-1">
                    <div class="w-full pt-[75%] bg-info rounded-lg"></div>
                    <div class="bold">Info Color</div>
                    <div>Hex : rgb(33, 158, 188)</div>
                    <div>Class : .bg-info</div>
                </div>
            </div>
        </div>
    </Card>
</template>

<script setup>
import Card from "@/Components/UI/Card.vue";
</script>
