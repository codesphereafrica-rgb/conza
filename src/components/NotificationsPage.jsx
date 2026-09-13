import React, { useEffect, useState } from 'react'
import { supabase } from '../lib/supabase'

export default function NotificationsPage({ userId }) {
    const [notifications, setNotifications] = useState([])
    const [openMenuId, setOpenMenuId] = useState(null)
    const [contentMissing, setContentMissing] = useState(false)

    useEffect(() => {
        if (!userId || !supabase) return undefined

        let mounted = true

        async function loadNotifications() {
            const { data, error } = await supabase
                .from('notifications')
                .select('*')
                .eq('user_id', userId)
                .order('created_at', { ascending: false })

            if (!error && mounted) {
                const notificationId = new URLSearchParams(window.location.search).get('notification_id')
                const contentIsMissing = new URLSearchParams(window.location.search).get('content_missing') === '1'

                if (contentIsMissing && notificationId) {
                    const { error: deleteError } = await supabase
                        .from('notifications')
                        .delete()
                        .eq('id', notificationId)
                        .eq('user_id', userId)

                    if (!deleteError) {
                        setNotifications((data || []).filter((item) => String(item.id) !== notificationId))
                        setContentMissing(true)
                        window.history.replaceState({}, '', '/notifications')
                        return
                    }
                }

                setNotifications(data || [])
            }
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

        if (notification.link) {
            const target = new URL(notification.link, window.location.origin)
            target.searchParams.set('notification_id', notification.id)
            window.location.href = target.pathname + target.search
        }
    }

    async function deleteNotification(notificationId) {
        if (!supabase) return

        const { error } = await supabase
            .from('notifications')
            .delete()
            .eq('id', notificationId)
            .eq('user_id', userId)

        if (!error) {
            setNotifications((current) => current.filter((item) => item.id !== notificationId))
            setOpenMenuId(null)
        }
    }

    async function deleteAllNotifications() {
        if (!supabase || !notifications.length) return

        const { error } = await supabase
            .from('notifications')
            .delete()
            .eq('user_id', userId)

        if (!error) setNotifications([])
    }

    return (
        <section className="notifications-page">
            <div className="notifications-heading">
                <h1>Notifications</h1>
                <button
                    type="button"
                    className="notifications-clear-button"
                    onClick={deleteAllNotifications}
                    disabled={!notifications.length}
                >
                    Supprimer toutes les notifications
                </button>
            </div>
            {contentMissing && (
                <p className="notification-empty">Ce contenu n'existe plus. La notification a été supprimée.</p>
            )}
            {notifications.length === 0 ? (
                <p className="notification-empty">Aucune notification.</p>
            ) : (
                <div className="notifications-list">
                    {notifications.map((notification) => (
                        <div
                            className={`notification-row${notification.is_read ? '' : ' is-unread'}`}
                            key={notification.id}
                            onClick={() => openNotification(notification)}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter' || event.key === ' ') openNotification(notification)
                            }}
                            role="button"
                            tabIndex="0"
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
                            <span className="notification-menu">
                                <button
                                    type="button"
                                    className="notification-menu-toggle"
                                    aria-label="Options de la notification"
                                    aria-expanded={openMenuId === notification.id}
                                    onClick={(event) => {
                                        event.stopPropagation()
                                        setOpenMenuId((current) => current === notification.id ? null : notification.id)
                                    }}
                                >
                                    ⋮
                                </button>
                                {openMenuId === notification.id && (
                                    <button
                                        type="button"
                                        className="notification-menu-delete"
                                        onClick={(event) => {
                                            event.stopPropagation()
                                            deleteNotification(notification.id)
                                        }}
                                    >
                                        Supprimer
                                    </button>
                                )}
                            </span>
                        </div>
                    ))}
                </div>
            )}
        </section>
    )
}