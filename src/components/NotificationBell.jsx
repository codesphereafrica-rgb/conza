import React, { useEffect, useState } from 'react'
import { supabase } from '../lib/supabase'

export default function NotificationBell({ userId }) {
    const [unreadCount, setUnreadCount] = useState(0)

    useEffect(() => {
        if (!userId || !supabase) return undefined

        async function loadUnreadCount() {
            const { count } = await supabase
                .from('notifications')
                .select('id', { count: 'exact', head: true })
                .eq('user_id', userId)
                .eq('is_read', false)

            setUnreadCount(count || 0)
        }

        loadUnreadCount()

        const channel = supabase
            .channel(`notifs-badge-${userId}`)
            .on(
                'postgres_changes',
                {
                    event: 'INSERT',
                    schema: 'public',
                    table: 'notifications',
                    filter: `user_id=eq.${userId}`,
                },
                () => setUnreadCount((count) => count + 1),
            )
            .subscribe()

        return () => {
            supabase.removeChannel(channel)
        }
    }, [userId])

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
                {unreadCount > 0 && <span className="notification-count">{unreadCount > 99 ? '99+' : unreadCount}</span>}
            </button>
        </div>
    )
}
