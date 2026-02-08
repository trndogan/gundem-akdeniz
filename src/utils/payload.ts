import { getPayload } from 'payload'
import config from '../payload.config'

let cachedPayload: any = null

export const getPayloadClient = async () => {
  if (!cachedPayload) {
    cachedPayload = await getPayload({ config })
  }
  return cachedPayload
}
