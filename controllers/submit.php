<?php

use Osmium\Core\Library\ServiceHooks;
use Osmium\Core\Library\ServiceLoader;
use Osmium\Core\Models\ServiceModel;
use Osmium\Services\RestockLeads\Models\RestockLead;
use Osmium\Services\RestockLeads\Models\RestockPageData;
use Osmium\Services\RestockLeads\Models\RestockProtection;
use Osmium\Services\RestockLeads\Models\RestockRequest;

/**
 * Notify-me endpoint (POST /api/restock-leads). Fields: email, pid, plus the form's token and the zip
 * honeypot, and the answer of any bot protection installed (form.verify). The visitor is always sent back to the page they came from (or the shop), with a one-time
 * restockNotice for the theme to show.
 */

$sg = $this->osmium->superGlobal;

$requestMethod = $sg->get(type: 'SERVER', name: 'REQUEST_METHOD');
$notPost = $requestMethod !== 'POST';
if ($notPost) {
    $this->osmium->header->redirect(targetURL: '/shop/', statusCode: 302);
}

$referrer = $_SERVER['HTTP_REFERER'] ?? '/shop/';
$back = \parse_url(url: $referrer, component: PHP_URL_PATH) ?: '/shop/'; // Only the path: never send a visitor to another site
$notice = fn(string $status, string $message) => RestockPageData::rememberNotice(session: $_SESSION, status: $status, message: $message);

$honeyPot = $sg->get(type: 'POST', name: 'zip');
$botDetected = !empty($honeyPot);

$submittedToken = $sg->get(type: 'POST', name: 'token');
$sessionToken = $sg->get(type: 'SESSION', name: 'token');
$tokenIsValid = !empty($submittedToken)
    && !empty($sessionToken)
    && \hash_equals(known_string: $sessionToken, user_string: $submittedToken);

$tokenIssuedAt = $sg->get(type: 'SESSION', name: 'token_issued_at');
$secondsSinceTokenIssued = $tokenIssuedAt ? (\time() - (int) $tokenIssuedAt) : 0;
$submittedTooFast = $secondsSinceTokenIssued < 4;

unset($_SESSION['token'], $_SESSION['token_issued_at']); // One token, one submission

// Any installed bot protection answers the form.verify hook; this service never names one
$protectionAnswers = (new ServiceHooks(new ServiceLoader(new ServiceModel($this->osmium->dataSource))))
    ->collect('form.verify', ['form' => 'restock', 'post' => $_POST, 'dataSource' => $this->osmium->dataSource]);
$protectionFailed = !RestockProtection::passed($protectionAnswers);

$looksLikeABot = $botDetected || !$tokenIsValid || $submittedTooFast || $protectionFailed;
if ($looksLikeABot) {
    $notice('failed', RestockRequest::TECHNICAL_PROBLEM); // Never say which check failed
    $this->osmium->header->redirect(targetURL: $back, statusCode: 302);
}

$request = RestockRequest::parse(['email' => $sg->get(type: 'POST', name: 'email'), 'pid' => $sg->get(type: 'POST', name: 'pid')]);
if ($request === null) {
    $notice('failed', 'Please enter a valid email address');
    $this->osmium->header->redirect(targetURL: $back, statusCode: 302);
}

(new RestockLead($this->osmium->dataSource))->create(productId: $request['productId'], email: $request['email']);

RestockPageData::rememberRequest(session: $_SESSION, productId: $request['productId']); // In the session, so this service sets no cookie of its own

$notice('sent', 'You will be notified when this product is back in stock.');
$this->osmium->header->redirect(targetURL: $back, statusCode: 302);
