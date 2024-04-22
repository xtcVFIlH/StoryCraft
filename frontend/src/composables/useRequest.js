import axios from 'axios';
import { useLocalStorage } from '@/composables/useLocalStorage';
import { ElMessageBox } from 'element-plus';

let tokenPromise = null;

export function useRequest() {
    const userToken = useLocalStorage('userToken', null);

    const request = (path, json = {}, query = {}, cancelToken = null) => {
        const uri = process.env.VUE_APP_BACKEND_URI;

        if (!uri) {
            return Promise.reject('未配置VUE_APP_BACKEND_URI');
        }

        if (userToken.value) {
            tokenPromise = Promise.resolve();
        } else if (!tokenPromise) {
            tokenPromise = ElMessageBox.prompt('请输入token', '提示', {
                confirmButtonText: '确定',
                showCancelButton: false,
                showClose: false,
                closeOnClickModal: false,
                closeOnPressEscape: false,
                inputPattern: /^[A-Za-z0-9]{10}$/,
                inputErrorMessage: 'token格式错误',
            }).then(({ value }) => {
                userToken.value = value;
            }).catch(() => {
                return Promise.reject('未提供token');
            });
        }

        return tokenPromise
            .then(() => {
                return axios.post(uri + path, json, {
                    params: {
                        ...query,
                        token: userToken.value,
                    },
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    cancelToken: cancelToken,
                });
            })
            .then((response) => {
                if (response.status !== 200) {
                    return Promise.reject(response.statusText);
                }
                if (response.data.code !== 0) {
                    return Promise.reject(response.data.message);
                }
                return Promise.resolve(response.data.data);
            })
    }

    return { request };
}