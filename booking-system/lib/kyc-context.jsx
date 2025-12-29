"use client"

import { createContext, useContext, useState, useEffect } from "react"
import { kycVerifications } from "./db"

const KYCContext = createContext()

export function KYCProvider({ children }) {
  const [verifications, setVerifications] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const savedVerifications = localStorage.getItem("kycVerifications")
    if (savedVerifications) {
      setVerifications(JSON.parse(savedVerifications))
    } else {
      setVerifications(kycVerifications)
      localStorage.setItem("kycVerifications", JSON.stringify(kycVerifications))
    }
    setLoading(false)
  }, [])

  const submitKYC = (userId, userType, documents) => {
    const existingKYC = verifications.find((v) => v.userId === userId)

    const newKYC = {
      id: existingKYC?.id || `kyc-${Date.now()}`,
      userId,
      userType,
      status: "pending",
      idImage: documents.idImage,
      citizenshipImage: documents.citizenshipImage,
      certificationImage: documents.certificationImage || null,
      submittedAt: new Date(),
      verifiedAt: null,
      verifiedBy: null,
      rejectionReason: null,
    }

    let updatedVerifications
    if (existingKYC) {
      updatedVerifications = verifications.map((v) => (v.userId === userId ? newKYC : v))
    } else {
      updatedVerifications = [...verifications, newKYC]
    }

    setVerifications(updatedVerifications)
    localStorage.setItem("kycVerifications", JSON.stringify(updatedVerifications))
    return newKYC
  }

  const getKYCByUserId = (userId) => {
    return verifications.find((v) => v.userId === userId)
  }

  const approveKYC = (kycId, adminId) => {
    const updatedVerifications = verifications.map((v) =>
      v.id === kycId
        ? {
            ...v,
            status: "verified",
            verifiedAt: new Date(),
            verifiedBy: adminId,
            rejectionReason: null,
          }
        : v,
    )
    setVerifications(updatedVerifications)
    localStorage.setItem("kycVerifications", JSON.stringify(updatedVerifications))
  }

  const rejectKYC = (kycId, adminId, reason) => {
    const updatedVerifications = verifications.map((v) =>
      v.id === kycId
        ? {
            ...v,
            status: "rejected",
            verifiedAt: new Date(),
            verifiedBy: adminId,
            rejectionReason: reason,
          }
        : v,
    )
    setVerifications(updatedVerifications)
    localStorage.setItem("kycVerifications", JSON.stringify(updatedVerifications))
  }

  const cancelKYC = (kycId, adminId) => {
    const updatedVerifications = verifications.map((v) =>
      v.id === kycId
        ? {
            ...v,
            status: "pending",
            verifiedAt: null,
            verifiedBy: null,
            rejectionReason: null,
          }
        : v,
    )
    setVerifications(updatedVerifications)
    localStorage.setItem("kycVerifications", JSON.stringify(updatedVerifications))
  }

  const getPendingVerifications = () => {
    return verifications.filter((v) => v.status === "pending")
  }

  return (
    <KYCContext.Provider
      value={{
        verifications,
        loading,
        submitKYC,
        getKYCByUserId,
        approveKYC,
        rejectKYC,
        cancelKYC,
        getPendingVerifications,
      }}
    >
      {children}
    </KYCContext.Provider>
  )
}

export function useKYC() {
  const context = useContext(KYCContext)
  if (!context) {
    throw new Error("useKYC must be used within KYCProvider")
  }
  return context
}
