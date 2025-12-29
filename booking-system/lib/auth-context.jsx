"use client"

import { createContext, useContext, useState, useEffect } from "react"
import { users, providers, admins } from "./db"

const AuthContext = createContext()

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const savedUser = localStorage.getItem("currentUser")
    if (savedUser) {
      setUser(JSON.parse(savedUser))
    }
    setLoading(false)
  }, [])

  const login = (email, password) => {
    // Check customers
    const foundUser = users.find((u) => u.email === email && u.password === password)
    if (foundUser) {
      setUser(foundUser)
      localStorage.setItem("currentUser", JSON.stringify(foundUser))
      return foundUser
    }

    // Check providers
    const foundProvider = providers.find((p) => p.email === email && p.password === password)
    if (foundProvider) {
      const providerUser = { ...foundProvider, role: "provider" }
      setUser(providerUser)
      localStorage.setItem("currentUser", JSON.stringify(providerUser))
      return providerUser
    }

    // Check admins
    const foundAdmin = admins.find((a) => a.email === email && a.password === password)
    if (foundAdmin) {
      setUser(foundAdmin)
      localStorage.setItem("currentUser", JSON.stringify(foundAdmin))
      return foundAdmin
    }

    return null
  }

  const signup = (name, email, password, role = "customer") => {
    const newUser = {
      id: `user-${Date.now()}`,
      name,
      email,
      password,
      role,
      phone: "",
      address: "",
      createdAt: new Date(),
    }
    users.push(newUser)
    setUser(newUser)
    localStorage.setItem("currentUser", JSON.stringify(newUser))
    return newUser
  }

  const logout = () => {
    setUser(null)
    localStorage.removeItem("currentUser")
  }

  return <AuthContext.Provider value={{ user, loading, login, signup, logout }}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error("useAuth must be used within AuthProvider")
  }
  return context
}
