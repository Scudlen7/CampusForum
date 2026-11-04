// 统一 API 请求（CSRF Token + JSON 解析 + 验证错误格式化）；Grid 数据加载
export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

export async function apiFetch(url, options = {}) {
    const headers = {
        Accept: 'application/json',
        ...(options.body && !(options.body instanceof FormData)
            ? { 'Content-Type': 'application/json' }
            : {}),
        ...options.headers,
    };

    if (options.method && options.method !== 'GET') {
        headers['X-CSRF-TOKEN'] = csrfToken();
        headers['X-Requested-With'] = 'XMLHttpRequest';
    }

    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const detail = formatValidationErrors(data.errors);
        throw new Error(detail || data.message || '请求失败');
    }

    return data;
}

function formatValidationErrors(errors) {
    if (!errors || typeof errors !== 'object') {
        return '';
    }

    return Object.values(errors).flat().join('；');
}

export function loadGridData(grid, url) {
    return apiFetch(url).then((result) => {
        grid.data.parse(result.data || []);
        return result;
    });
}
