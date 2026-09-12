import React from 'react'

export default function NotificationBell() {
    return (
        <div className="notification-bell-wrapper">
            <button
                type="button"
                className="notification-bell"
                aria-label="Notifications"
                title="Notifications"
                onClick={() => { window.location.href = '/notifications' }}
            >
                <span aria-hidden="true">🔔</span>
            </button>
        </div>
    )
}
