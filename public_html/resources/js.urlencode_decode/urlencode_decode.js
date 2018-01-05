function urlencode(str) {
return escape(str).replace('+', '%2B').replace('%20', '+').replace('*', '%2A').replace('/', '%2F').replace('@', '%40');
}

function urldecode(str) {
return unescape(str.replace('+', ' '));
}