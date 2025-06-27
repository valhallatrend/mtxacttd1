if (!isset($_GET['account'])) {
    http_response_code(403);
    exit("🔒 Access Denied");
}
