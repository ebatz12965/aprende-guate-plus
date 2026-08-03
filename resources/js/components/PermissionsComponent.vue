<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

const permissions = ref([]);

const fetchPermissions = async () => {
    try {
        const response = await axios.get('/admin/permissions'); // Petición a la API
        permissions.value = response.data;
    } catch (error) {
        console.error('Error al obtener los permisos:', error);
    }
};

onMounted(fetchPermissions);
</script>

<template>
    <div>
        <h3 class="text-lg font-bold mb-4">Permisos del Sistema</h3>
        <ul class="space-y-2">
            <li v-for="permission in permissions" :key="permission.id" class="p-4 border rounded-md">
                {{ permission.name }}
            </li>
        </ul>
    </div>
</template>

<style scoped>

</style>
