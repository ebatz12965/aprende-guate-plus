<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

const roles = ref([]);

const fetchRoles = async () => {
    try {
        const response = await axios.get('/admin/roles'); // Petición a la API
        roles.value = response.data;
    } catch (error) {
        console.error('Error al obtener los roles:', error);
    }
};

onMounted(fetchRoles);
</script>

<template>
    <div>
        <h3 class="text-lg font-bold mb-4">Roles del Sistema</h3>
        <ul class="space-y-2">
            <li v-for="role in roles" :key="role.id" class="p-4 border rounded-md">
                {{ role.name }}
            </li>
        </ul>
    </div>
</template>

<style scoped>

</style>
