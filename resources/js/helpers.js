export function getRoles(data) {
    const checkRole = JSON.parse(data);
    const roles = checkRole[0].toString().toLowerCase();
    return roles;
}

export function getToken(key) {
    const token = localStorage.getItem(key)
        ? JSON.parse(localStorage.getItem(key))
        : null;
    return token;
}
