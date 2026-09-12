import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import NotificationBell from '../../src/components/NotificationBell.jsx';
import NotificationsPage from '../../src/components/NotificationsPage.jsx';

const notificationRoot = document.getElementById('notification-bell-root');

if (notificationRoot) {
	createRoot(notificationRoot).render(React.createElement(NotificationBell, {
		userId: notificationRoot.dataset.userId,
	}));
}

const notificationsPageRoot = document.getElementById('notifications-page-root');

if (notificationsPageRoot) {

	createRoot(notificationsPageRoot).render(React.createElement(NotificationsPage, {
		userId: notificationsPageRoot.dataset.userId,
	}));
}
