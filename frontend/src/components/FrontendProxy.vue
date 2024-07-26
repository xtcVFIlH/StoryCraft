<template>
    <div style="display: none;"></div>
</template>

<script setup>

import { onMounted, onUnmounted, ref } from 'vue';
import axios from 'axios';
import { useRequest } from '@/composables/useRequest';
import { useLocalStorage } from '@/composables/useLocalStorage';

const { request } = useRequest();

/**
 * 前端代理的ID，随机的16位字符串
 * @type {import('vue').Ref<string>}
 */
const proxyId = useLocalStorage('frontend_proxy_id');
proxyId.value = Math.random().toString(36).substring(2, 10) + Math.random().toString(36).substring(2, 10);

/**
 * 是否正在轮询
 * @type {import('vue').Ref<boolean>}
 */
const isPolling = ref(false);

/**
 * 查询当前后端是否开启了前端代理
 * @returns {Promise<boolean>}
 */
 const queryIsProxyUsed = async () => {
    const response = await request(
        '/frontend-proxy/is-used',
        {},
        {},
        null,
        true
    );
    return !!response;
};

/**
 * 轮询
 * @returns {void}
 */
const polling = async () => {
    let proxyResponseData = null;
    try {
        if (!isPolling.value) {
            return;
        }
        // 等待需要代理的后端请求
        let responseData = null;
        try {
            responseData = await request(
                '/frontend-proxy/wait-request',
                {},
                {},
                null,
                true
            );
            if (!responseData) {
                throw new Error('No request waiting');
            }
        }
        catch (error) {
            return;
        }
        // 发送代理请求
        const { url, json } = responseData;
        const proxyResponse = await axios.post(
            url,
            json,
            {
                headers: {
                    'Content-Type': 'application/json',
                },
            }
        );
        proxyResponseData = proxyResponse.data;
    }
    catch (error) {
        proxyResponseData = {
            error: error.toString(),
        };
    }
    finally {
        if (isPolling.value) {
            if (proxyResponseData) {
                await request(
                    '/frontend-proxy/set-response',
                    {
                        'response_data': proxyResponseData,
                    },
                    {},
                    null,
                    true
                );
            }
            polling();
        }
    }
};

onMounted(async () => {
    isPolling.value = await queryIsProxyUsed();
    if (!isPolling.value) {
        return;
    }
    polling();
});
onUnmounted(() => {
    isPolling.value = false;
});

</script>

<style scoped>
</style>