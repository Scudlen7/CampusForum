<template>
    <div ref="root" class="dhx-app-shell auth-page"></div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { formatErrors, mountAuthPage, submitAuthForm } from '../dhx/authPage';

const props = defineProps({
    errors: { type: Object, default: () => ({}) },
    old: { type: Object, default: () => ({}) },
});

const root = ref(null);
let page = null;

onMounted(() => {
    page = mountAuthPage(root.value, {
        title: '登录 - 华东师范大学校园论坛',
        errorMessage: formatErrors(props.errors),
        windowHeight: 450,
        formRows: [
            {
                type: 'input',
                name: 'login',
                label: '用户名或邮箱',
                labelPosition: 'top',
                placeholder: '请输入用户名或邮箱',
                required: true,
                value: props.old.login || '',
            },
            {
                type: 'input',
                inputType: 'password',
                name: 'password',
                label: '密码',
                labelPosition: 'top',
                placeholder: '请输入密码',
                required: true,
            },
            {
                type: 'checkbox',
                name: 'remember',
                label: '记住我',
                checked: props.old.remember === 'on' || props.old.remember === true,
            },
            {
                type: 'button',
                name: 'submit',
                text: '登录',
                submit: true,
                size: 'medium',
                view: 'flat',
                color: 'primary',
                full: true,
            },
        ],
        footerLinks: [
            { name: 'gotoRegister', text: '还没有账号？立即注册', href: '/register' },
            { name: 'gotoHome', text: '返回首页', href: '/' },
        ],
        onSubmit(values) {
            submitAuthForm('/login', {
                login: values.login,
                password: values.password,
                remember: values.remember ? 'on' : undefined,
            });
        },
    });
});

onBeforeUnmount(() => {
    page?.destroy();
});
</script>
