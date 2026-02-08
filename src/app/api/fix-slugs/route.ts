import config from '../../../payload.config'
import { getPayload } from 'payload'
import { NextResponse } from 'next/server'

function turkishSlug(text: string): string {
  const turkishMap: Record<string, string> = {
    'ç': 'c', 'ğ': 'g', 'ı': 'i', 'ö': 'o', 'ş': 's', 'ü': 'u',
    'Ç': 'c', 'Ğ': 'g', 'İ': 'i', 'Ö': 'o', 'Ş': 's', 'Ü': 'u',
  };
  return text
    .split('')
    .map((char) => turkishMap[char] || char)
    .join('')
    .toLowerCase()
    .replace(/&/g, '-ve-')
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9-]/g, '')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');
}

export const GET = async () => {
  const payload = await getPayload({ config })

  try {
    // Fix category slugs
    const categories = await payload.find({ collection: 'categories', limit: 100 })
    for (const cat of categories.docs) {
      const newSlug = turkishSlug(cat.name as string)
      if (cat.slug !== newSlug) {
        await payload.update({
          collection: 'categories',
          id: cat.id,
          data: { slug: newSlug },
        })
        console.log(`Category slug updated: ${cat.name} -> ${newSlug}`)
      }
    }

    // Fix article slugs
    const articles = await payload.find({ collection: 'articles', limit: 100 })
    for (const article of articles.docs) {
      const newSlug = turkishSlug(article.title as string)
      if (article.slug !== newSlug) {
        await payload.update({
          collection: 'articles',
          id: article.id,
          data: { slug: newSlug },
        })
        console.log(`Article slug updated: ${article.title} -> ${newSlug}`)
      }
    }

    return NextResponse.json({ success: true, message: 'Slugs fixed' })
  } catch (err) {
    console.error('Fix slugs failed:', err)
    return NextResponse.json({ success: false, error: String(err) }, { status: 500 })
  }
}
