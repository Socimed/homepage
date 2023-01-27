<?php

/**
 * stripe
 * 
 * @package BuddyEngine
 * @author BuddyEngine
 */

// fetch bootloader
require('bootloader.php');

// user access (simple)
if (!$user->_logged_in) {
  user_login();
}

try {
  if ($_GET['status'] == 'success') {
    switch ($_GET['handle']) {
      case 'packages':
        // valid inputs
        if (!isset($_GET['package_id']) || !is_numeric($_GET['package_id'])) {
          _error(404);
        }

        // get package
        $package = $user->get_package($_GET['package_id']);
        if (!$package) {
          _error(404);
        }

        // check payment
        $payment = stripe_check();
        if ($payment) {
          /* update user package */
          $user->update_user_package($package['package_id'], $package['name'], $package['price'], $package['verification_badge_enabled']);
          /* redirect */
          redirect("/upgraded");
        }
        break;

      case 'wallet':
        // check payment
        $payment = stripe_check();
        if ($payment) {
          /* update user wallet balance */
          $db->query(sprintf("UPDATE users SET user_wallet_balance = user_wallet_balance + %s WHERE user_id = %s", secure($_SESSION['wallet_replenish_amount']), secure($user->_data['user_id'], 'int'))) or _error("SQL_ERROR_THROWEN");
          /* wallet transaction */
          $user->wallet_set_transaction($user->_data['user_id'], 'recharge', 0, $_SESSION['wallet_replenish_amount'], 'in');
          /* redirect */
          redirect("/wallet?wallet_replenish_succeed");
        }
        break;

      case 'donate':
        // valid inputs
        if (!isset($_GET['post_id']) || !is_numeric($_GET['post_id'])) {
          _error(404);
        }

        // check payment
        $payment = stripe_check();
        if ($payment) {
          /* funding donation */
          $user->funding_donation($_GET['post_id'], $_SESSION['donation_amount']);
          /* redirect */
          redirect("/posts/" . $_GET['post_id']);
        }
        break;

      case 'subscribe':
        // valid inputs
        if (!isset($_GET['node_id']) || !is_numeric($_GET['node_id'])) {
          _error(404);
        }
        if (!isset($_GET['node_type']) || !in_array($_GET['node_type'], ['user', 'page', 'group'])) {
          _error(404);
        }

        // check payment
        $payment = stripe_check();
        if ($payment) {
          /* subscribe to node */
          $node_link = $user->subscribe($_GET['node_id'], $_GET['node_type']);
          /* redirect */
          redirect($node_link);
        }
        break;

      default:
        _error(404);
        break;
    }
  }
  redirect();
} catch (Exception $e) {
  _error('System Message', $e->getMessage());
}
