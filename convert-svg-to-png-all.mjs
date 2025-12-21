import { Resvg } from '@resvg/resvg-js'
import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

// Read SVG file
const svgContent = fs.readFileSync(path.join(__dirname, 'public', 'icon.svg'), 'utf-8')

// Common icon sizes
const sizes = [
  { name: 'icon-16.png', size: 16 },
  { name: 'icon-32.png', size: 32 },
  { name: 'icon-48.png', size: 48 },
  { name: 'icon-64.png', size: 64 },
  { name: 'icon-128.png', size: 128 },
  { name: 'icon-256.png', size: 256 },
  { name: 'icon.png', size: 512 }, // Default
]

console.log('🔄 Converting SVG to PNG in multiple sizes...\n')

sizes.forEach(({ name, size }) => {
  const resvg = new Resvg(svgContent, {
    fitTo: {
      mode: 'width',
      value: size,
    },
    font: {
      loadSystemFonts: false,
    },
  })

  const pngData = resvg.render()
  const pngBuffer = pngData.asPng()
  
  const outputPath = path.join(__dirname, 'public', name)
  fs.writeFileSync(outputPath, pngBuffer)
  
  console.log(`✅ ${name} (${pngData.width}x${pngData.height}px)`)
})

console.log('\n🎉 All PNG files created successfully!')
console.log('📁 Location: public/')
