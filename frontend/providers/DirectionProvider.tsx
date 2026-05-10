'use client';

import { createContext, useContext, useEffect, useState } from 'react';

type Dir = 'ltr' | 'rtl';

const DirectionContext = createContext<{ dir: Dir; toggle: () => void }>({
  dir: 'ltr',
  toggle: () => {},
});

export function DirectionProvider({ children }: { children: React.ReactNode }) {
  const [dir, setDir] = useState<Dir>('ltr');

  useEffect(() => {
    const stored = localStorage.getItem('dir') as Dir | null;
    if (stored) {
      setDir(stored);
      document.documentElement.setAttribute('dir', stored);
      document.documentElement.setAttribute('lang', stored === 'rtl' ? 'ar' : 'en');
    }
  }, []);

  const toggle = () => {
    setDir((prev) => {
      const next = prev === 'ltr' ? 'rtl' : 'ltr';
      document.documentElement.setAttribute('dir', next);
      document.documentElement.setAttribute('lang', next === 'rtl' ? 'ar' : 'en');
      localStorage.setItem('dir', next);
      return next;
    });
  };

  return <DirectionContext.Provider value={{ dir, toggle }}>{children}</DirectionContext.Provider>;
}

export function useDirection() {
  return useContext(DirectionContext);
}
