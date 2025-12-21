import { Resvg } from '@resvg/resvg-js'
import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

// Read SVG file
const svgContent = fs.readFileSync(path.join(__dirname, 'public', 'icon.svg'), 'utf-8')

// Parse and render SVG to PNG
const resvg = new Resvg(svgContent, {
  fitTo: {
    mode: 'width',
    value: 512, // Output width in pixels
  },
  font: {
    loadSystemFonts: false,
  },
})

// Render to PNG buffer
const pngData = resvg.render()
const pngBuffer = pngData.asPng()

// Save PNG file
const outputPath = path.join(__dirname, 'public', 'icon.png')
fs.writeFileSync(outputPath, pngBuffer)

console.log('✅ SVG successfully converted to PNG!')
console.log(`📁 Output: ${outputPath}`)
console.log(`📐 Size: ${pngData.width}x${pngData.height}px`)
