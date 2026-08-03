<template>
    <div>
        <h2 class="text-2xl font-bold mb-4">Usuarios registrados</h2>
        <table class="min-w-full border border-gray-200">
            <thead class="bg-gray-200">
            <tr>
                <th class="py-2 px-4 text-left">ID</th>
                <th class="py-2 px-4 text-left">Nombre</th>
                <th class="py-2 px-4 text-left">Email</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="user in users" :key="user.id" class="border-b">
                <td class="py-2 px-4">{{ user.id }}</td>
                <td class="py-2 px-4">{{ user.name }}</td>
                <td class="py-2 px-4">{{ user.email }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
import { ref, onMounted } from "vue";

export default {
    name: "UserIndex",
    setup() {
        const users = ref([]);

        onMounted(() => {
            const el = document.getElementById("vue-users-app");
            if (el) {
                users.value = JSON.parse(el.dataset.users || "[]");
            }
        });

        return { users };
    },
};
</script>
