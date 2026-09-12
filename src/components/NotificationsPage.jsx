import React, { useEffect, useState } from 'react'
import { supabase } from '../lib/supabase'

export default function NotificationsPage({ userId }) {
    const [notifications, setNotifications] = useState([])

    useEffect(() => {
        if (!userId || !supabase) return undefined

        let mounted = true

        async function loadNotifications() {
            const { data, error } = await supabase
                .from('notifications')
                .select('*')
                .eq('user_id', userId)
                .order('created_at', { ascending: false })

            if (!error && mounted) setNotifications(data || [])
        }

        loadNotifications()

        const channel = supabase
            .channel(`notifications-page-${userId}`)
            .on(
                'postgres_changes',
                {
                    event: 'INSERT',
                    schema: 'public',
                    table: 'notifications',
                    filter: `user_id=eq.${userId}`,
                },
                (payload) => setNotifications((current) => [payload.new, ...current]),
            )
            .subscribe()

        return () => {
            mounted = false
            supabase.removeChannel(channel)
        }
    }, [userId])

    async function openNotification(notification) {
        if (!notification.is_read && supabase) {
            setNotifications((current) => current.map((item) => (
                item.id === notification.id ? { ...item, is_read: true } : item
            )))
            await supabase.from('notifications').update({ is_read: true }).eq('id', notification.id)
        }

        if (notification.link) window.location.href = notification.link
    }

    return (
        <section className="notifications-page">
            <h1>Notifications</h1>
            {notifications.length === 0 ? (
                <p className="notification-empty">Aucune notification.</p>
            ) : (
                <div className="notifications-list">
                    {notifications.map((notification) => (
                        <button
                            type="button"
                            className={`notification-row${notification.is_read ? '' : ' is-unread'}`}
                            key={notification.id}
                            onClick={() => openNotification(notification)}
                        >
                            {notification.author_avatar ? (
                                <img className="notification-author-avatar" src={notification.author_avatar} alt="" />
                            ) : (
                                <span className="notification-author-avatar notification-author-fallback" aria-hidden="true">
                                    {(notification.author_name || 'U').slice(0, 1).toUpperCase()}
                                </span>
                            )}
                            <span className="notification-row-content">
                                <span><strong>{notification.author_name || 'Utilisateur'}</strong> a fait une nouvelle publication</span>
                                <small>{new Date(notification.created_at).toLocaleString('fr-FR')}</small>
                            </span>
                        </button>
                    ))}
                </div>
            )}
        </section>
    )
}