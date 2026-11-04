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
        title: '注册 - 华东师范大学校园论坛',
        errorMessage: formatErrors(props.errors),
        windowHeight: 520,
        formRows: [
            {
                type: 'input',
                name: 'username',
                label: '用户名',
                labelPosition: 'top',
                placeholder: '3-32 位字母、数字、下划线或短横线',
                required: true,
                value: props.old.username || '',
            },
            {
                type: 'input',
                name: 'email',
                label: '邮箱',
                labelPosition: 'top',
                placeholder: '建议使用校园邮箱',
                required: true,
                value: props.old.email || '',
            },
            {
                type: 'input',
                inputType: 'password',
                name: 'password',
                label: '密码',
                labelPosition: 'top',
                placeholder: '至少 8 位',
                required: true,
            },
            {
                type: 'input',
                inputType: 'password',
                name: 'password_confirmation',
                label: '确认密码',
                labelPosition: 'top',
                placeholder: '再次输入密码',
                required: true,
            },
            {
                type: 'input',
                name: 'nickname',
                label: '昵称（可选）',
                labelPosition: 'top',
                placeholder: '个人主页显示名称',
                value: props.old.nickname || '',
            },
            {
                type: 'button',
                name: 'submit',
                text: '注册',
                submit: true,
                size: 'medium',
                view: 'flat',
                color: 'primary',
                full: true,
            },
        ],
        footerLinks: [
            { name: 'gotoLogin', text: '已有账号？去登录', href: '/login' },
            { name: 'gotoHome', text: '返回首页', href: '/' },
        ],
        onSubmit(values) {
            submitAuthForm('/register', {
                username: values.username,
                email: values.email,
                password: values.password,
                password_confirmation: values.password_confirmation,
                nickname: values.nickname,
            });
        },
    });
});

onBeforeUnmount(() => {
    page?.destroy();
});
</script>
