"use client"

import { createContext, useContext, useState } from "react"
import { bookings, reviews, messages, notifications } from "./db"

const BookingContext = createContext()

export function BookingProvider({ children }) {
  const [allBookings, setAllBookings] = useState(bookings)
  const [allReviews, setAllReviews] = useState(reviews)
  const [allMessages, setAllMessages] = useState(messages)
  const [allNotifications, setAllNotifications] = useState(notifications)
  const [providerRatings, setProviderRatings] = useState({})

  const createBooking = (booking) => {
    const newBooking = {
      ...booking,
      id: `booking-${Date.now()}`,
      status: booking.status || "pending",
      createdAt: new Date(),
    }
    setAllBookings([...allBookings, newBooking])

    const newNotification = {
      id: `notif-${Date.now()}`,
      userId: newBooking.providerId,
      type: "new_booking",
      title: "New Booking Request",
      message: `You have a new booking request for ${newBooking.title}`,
      read: false,
      createdAt: new Date(),
    }
    setAllNotifications((prev) => [...prev, newNotification])

    return newBooking
  }

  const updateBooking = (bookingId, updates) => {
    const booking = allBookings.find((b) => b.id === bookingId)
    if (booking && updates.status === "confirmed" && booking.status !== "confirmed") {
      const customerNotification = {
        id: `notif-${Date.now()}-cust`,
        userId: booking.customerId,
        type: "booking_confirmed",
        title: "Booking Confirmed",
        message: `Your booking for ${booking.title} has been confirmed by the provider`,
        read: false,
        createdAt: new Date(),
      }
      setAllNotifications((prev) => [...prev, customerNotification])
    }

    if (booking && updates.status === "completed" && booking.status !== "completed") {
      const completedNotification = {
        id: `notif-${Date.now()}-complete`,
        userId: booking.customerId,
        type: "booking_completed",
        title: "Service Completed",
        message: `Your booking for ${booking.title} has been completed. Amount: $${updates.chargeAmount || booking.totalPrice}`,
        read: false,
        createdAt: new Date(),
      }
      setAllNotifications((prev) => [...prev, completedNotification])
    }

    setAllBookings(allBookings.map((b) => (b.id === bookingId ? { ...b, ...updates } : b)))
  }

  const addReview = (review) => {
    const newReview = {
      ...review,
      id: `review-${Date.now()}`,
      createdAt: new Date(),
    }
    setAllReviews([...allReviews, newReview])
    return newReview
  }

  const sendMessage = (message) => {
    const newMessage = {
      ...message,
      id: `msg-${Date.now()}`,
      createdAt: new Date(),
    }
    setAllMessages([...allMessages, newMessage])
    return newMessage
  }

  const addNotification = (notification) => {
    const newNotification = {
      ...notification,
      id: `notif-${Date.now()}`,
      read: false,
      createdAt: new Date(),
    }
    setAllNotifications([...allNotifications, newNotification])
    return newNotification
  }

  const markNotificationAsRead = (notificationId) => {
    setAllNotifications(allNotifications.map((n) => (n.id === notificationId ? { ...n, read: true } : n)))
  }

  const rateProvider = (providerId, rating, comment = "") => {
    const newRating = {
      id: `rating-${Date.now()}`,
      providerId,
      rating,
      comment,
      createdAt: new Date(),
    }
    setProviderRatings({
      ...providerRatings,
      [providerId]: [...(providerRatings[providerId] || []), newRating],
    })
    return newRating
  }

  const getProviderAverageRating = (providerId) => {
    const ratings = providerRatings[providerId] || []
    if (ratings.length === 0) return 0
    const sum = ratings.reduce((acc, r) => acc + r.rating, 0)
    return (sum / ratings.length).toFixed(1)
  }

  return (
    <BookingContext.Provider
      value={{
        bookings: allBookings,
        reviews: allReviews,
        messages: allMessages,
        notifications: allNotifications,
        providerRatings,
        createBooking,
        updateBooking,
        addReview,
        sendMessage,
        addNotification,
        markNotificationAsRead,
        rateProvider,
        getProviderAverageRating,
      }}
    >
      {children}
    </BookingContext.Provider>
  )
}

export function useBooking() {
  const context = useContext(BookingContext)
  if (!context) {
    throw new Error("useBooking must be used within BookingProvider")
  }
  return context
}
