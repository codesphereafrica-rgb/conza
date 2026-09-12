import React, { useEffect, useState } from 'react'
import { supabase } from '../lib/supabase'

function playNotificationSound() {
    try {
        const audioContext = new window.AudioContext()
        const oscillator = audioContext.createOscillator()
        const gain = audioContext.createGain()

        oscillator.type = 'sine'
        oscillator.frequency.value = 880
        gain.gain.setValueAtTime(0.08, audioContext.currentTime)
        gain.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.18)
        oscillator.connect(gain)
        gain.connect(audioContext.destination)
        oscillator.start()
        oscillator.stop(audioContext.currentTime + 0.18)
    } catch {
        // Browsers can block notification audio until the user interacts with the page.
    }
}

export default function NotificationBell({ userId }) {
    const [notifications, setNotifications] = useState([])
    const [isOpen, setIsOpen] = useState(false)
    const [toast, setToast] = useState('')
    const [isAnimating, setIsAnimating] = useState(false)
    const unreadCount = notifications.filter((notification) => !notification.is_read).length

    useEffect(() => {
        if (!userId || !supabase) {
            return undefined
        }

        let isMounted = true

        async function loadNotifications() {
            const { data, error } = await supabase
                .from('notifications')
                .select('*')
                .eq('user_id', userId)
                .order('created_at', { ascending: false })

            console.log('notifs data:', data, 'error:', error)

            if (!error && isMounted) {
                setNotifications(data || [])
            }
        }

        loadNotifications()

        const channel = supabase
            .channel(`notifs-${userId}`)
            .on(
                'postgres_changes',
                {
                    event: 'INSERT',
                    schema: 'public',
                    table: 'notifications',
                    filter: `user_id=eq.${userId}`,
                },
                (payload) => {
                    setNotifications((current) => [payload.new, ...current])
                    setToast('Nouvelle publication')
                    setIsAnimating(true)
                    playNotificationSound()
                    window.setTimeout(() => setToast(''), 3500)
                    window.setTimeout(() => setIsAnimating(false), 700)
                },
            )
            .subscribe()

        return () => {
            isMounted = false
            supabase.removeChannel(channel)
        }
    }, [userId])

    async function markAllAsRead() {
        if (!supabase || !userId || unreadCount === 0) {
            return
        }

        setNotifications((current) => current.map((item) => ({ ...item, is_read: true })))

        await supabase
            .from('notifications')
            .update({ is_read: true })
            .eq('user_id', userId)
            .eq('is_read', false)
    }

    return (
        <div className="notification-bell-wrapper">
            <button
                type="button"
                className={`notification-bell${isAnimating ? ' notification-bell-animated' : ''}`}
                aria-label={`Notifications${unreadCount ? ` (${unreadCount} non lues)` : ''}`}
                title="Notifications"
                onClick={() => {
                    setIsOpen((open) => !open)
                    markAllAsRead()
                }}
            >
                <span aria-hidden="true">🔔</span>
                {unreadCount > 0 && <span className="notification-count">{unreadCount > 99 ? '99+' : unreadCount}</span>}
            </button>
            {toast && <div className="notification-toast" role="status">{toast}</div>}
            {isOpen && (
                <div className="notification-panel" role="dialog" aria-label="Notifications">
                    <div className="notification-panel-header">Notifications</div>
                    {notifications.length === 0 ? (
                        <p className="notification-empty">Aucune notification.</p>
                    ) : (
                        <ul className="notification-list">
                            {notifications.map((notification) => (
                                <li key={notification.id} className={notification.is_read ? '' : 'is-unread'}>
                                    <button type="button" onClick={() => markAllAsRead()}>
                                        <strong>{notification.title || 'Nouvelle notification'}</strong>
                                        <span>{notification.message || ''}</span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}
        </div>
    )
}
