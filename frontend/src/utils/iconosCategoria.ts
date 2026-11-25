import type { Component } from 'vue'
import { Wheat, Milk, Droplet, CupSoda, Apple, SprayCan, ShoppingBasket, Beef, Fish, Package } from 'lucide-vue-next'
import type { IconoCategoria } from './imagenProducto'

/** Ícono de cada categoría: lo comparten la imagen del producto y los accesos del inicio. */
export const ICONOS_CATEGORIA: Record<IconoCategoria, Component> = {
  granos: Wheat,
  lacteos: Milk,
  aceites: Droplet,
  bebidas: CupSoda,
  frutas: Apple,
  aseo: SprayCan,
  carnes: Beef,
  pescados: Fish,
  despensa: Package,
  generico: ShoppingBasket,
}
